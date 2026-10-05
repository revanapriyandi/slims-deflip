"""Build the DeFlip deployment bundle from reviewed plugin and host files."""

import argparse
import difflib
import hashlib
import json
import re
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile


HOST_FILES = {
    "lib/contents/fstream.inc.php": "original-fstream.inc.php",
    "lib/contents/fstream-pdf.inc.php": "original-fstream-pdf.inc.php",
    "lib/detail.inc.php": "original-detail.inc.php",
    "admin/modules/bibliography/pop_attach.php": "original-pop_attach.php",
    "admin/modules/reporting/spreadsheet.php": "original-spreadsheet.php",
    "js/updater.js": "original-updater.js",
    "lib/Filesystems/Stream.php": "original-Stream.php",
    "repository/.htaccess": "original-repository.htaccess",
}
PLUGIN_DIRECTORIES = ("assets", "migration", "pages", "src", "viewer", "views")
PLUGIN_FILES = ("dflip.plugin.php", "helper.php", "README.md", "INSTALL.md", "LICENSE", "THIRD_PARTY.md")


def sha256(data: bytes) -> str:
    return hashlib.sha256(data).hexdigest()


def build(baseline: Path, output: Path) -> Path:
    plugin = Path(__file__).resolve().parents[1]
    workspace = plugin.parent.parent
    version_match = re.search(r"\* Version:\s*([0-9.]+)", (plugin / "dflip.plugin.php").read_text())
    host_version_match = re.search(
        r"define\('SENAYAN_VERSION_TAG', 'v([^']+)'\)",
        (workspace / "sysconfig.inc.php").read_text(),
    )
    if not version_match or not host_version_match:
        raise ValueError("Cannot determine plugin or SLiMS version")
    version, host_version = version_match[1], host_version_match[1]
    if host_version != "9.8.0":
        raise ValueError("Host integration must be reviewed for versions other than SLiMS 9.8.0")
    if output == plugin or plugin in output.parents:
        raise ValueError("Choose an output directory outside the plugin")

    payload = {}
    for directory in PLUGIN_DIRECTORIES:
        for path in sorted((plugin / directory).rglob("*")):
            if path.is_symlink():
                raise ValueError(f"Symlinks are not release payloads: {path}")
            if path.is_file():
                if path.suffix.lower() in {".bak", ".log", ".zip", ".pyc", ".sql"} or path.name.startswith("."):
                    raise ValueError(f"Unexpected local artifact in release payload: {path}")
                payload[f"plugins/deflip/{path.relative_to(plugin).as_posix()}"] = path.read_bytes()
    for name in PLUGIN_FILES:
        payload[f"plugins/deflip/{name}"] = (plugin / name).read_bytes()

    host_manifest = []
    patch = []
    for relative, original_name in HOST_FILES.items():
        before = (baseline / original_name).read_bytes()
        after = (workspace / relative).read_bytes()
        host_manifest.append({"path": relative, "baseline_sha256": sha256(before), "updated_sha256": sha256(after)})
        payload[f"integration/files/{relative}"] = after
        changes = difflib.unified_diff(
            before.decode("utf-8-sig").replace("\r\n", "\n").splitlines(keepends=True),
            after.decode("utf-8-sig").replace("\r\n", "\n").splitlines(keepends=True),
            fromfile=f"a/{relative}", tofile=f"b/{relative}", lineterm="\n",
        )
        for line in changes:
            patch.append(line if line.endswith("\n") else line + "\n\\ No newline at end of file\n")
    payload[f"integration/slims-{host_version}.patch"] = "".join(patch).encode("utf-8")
    payload["INSTALL.md"] = (plugin / "INSTALL.md").read_bytes()
    manifest = {
        "plugin": "DeFlip", "version": version, "slims_version": host_version,
        "deployment_status": "staging_acceptance_required",
        "database_migration": "Original migration 1 only; no new migration in this release",
        "host_integration": host_manifest,
        "files": [{"path": name, "sha256": sha256(data)} for name, data in sorted(payload.items())],
    }
    payload["manifest.json"] = (json.dumps(manifest, indent=2) + "\n").encode("utf-8")
    output.mkdir(parents=True, exist_ok=True)
    archive = output / f"deflip-{version}-slims-{host_version}.zip"
    with ZipFile(archive, "x", compression=ZIP_DEFLATED) as package:
        for name, data in sorted(payload.items()):
            package.writestr(name, data)
    checksum = archive.with_suffix(archive.suffix + ".sha256")
    checksum.write_text(f"{sha256(archive.read_bytes())}  {archive.name}\n", encoding="utf-8")
    return archive


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--baseline-dir", type=Path, required=True)
    parser.add_argument("--output-dir", type=Path, required=True)
    args = parser.parse_args()
    try:
        archive = build(args.baseline_dir.resolve(strict=True), args.output_dir.resolve())
    except (OSError, ValueError) as error:
        parser.exit(1, f"Release build failed: {error}\n")
    print(archive)


if __name__ == "__main__":
    main()
