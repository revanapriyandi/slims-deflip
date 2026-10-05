<?php

namespace DeFlip;

/** Keeps SLiMS report behavior with bounded paging and semantic table headers. */
final class ReportGrid extends \report_datagrid
{
    public function createDataGrid($database, $tables = '', $pageSize = 30, $editable = false)
    {
        $originalQuery = $_GET;
        $groupBy = $this->sql_group_by;
        // These reports define their own ordering; the host accepts arbitrary sort selectors.
        unset($_GET[$this->table_ID . 'fld'], $_GET['dir']);
        $_GET['page'] = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => [
            'min_range' => 1, 'max_range' => intdiv(PHP_INT_MAX, $pageSize),
        ]]) ?: 1;
        $this->current_page = $_GET['page'];
        try {
            $output = parent::createDataGrid($database, $tables, $pageSize, $editable);
            if (!$this->num_rows && $this->current_page > 1 && !$database->error) {
                // The host skips FOUND_ROWS when an offset produces no rows.
                $result = $database->query('SELECT FOUND_ROWS()');
                if (!$result) {
                    return '<div class="alert alert-danger" role="alert">' . dflipEscape(__('Unable to load the report. Please try again.')) . '</div>';
                }
                $total = (int) ($result->fetch_row()[0] ?? 0);
                $this->current_page = $_GET['page'] = max(1, (int) ceil($total / $pageSize));
                if ($total) {
                    $this->sql_group_by = $groupBy;
                    $output = parent::createDataGrid($database, $tables, $pageSize, $editable);
                }
            }
            return $output;
        } finally {
            $_GET = $originalQuery;
        }
    }

    public function printTable()
    {
        if (!isset($this->table_row[0])) return parent::printTable();
        $output = '<table ' . $this->table_attr . '><thead><tr ' . $this->table_header_attr . '>';
        foreach ($this->table_row[0]->fields as $column => $field) {
            $width = isset($this->column_width[$column]) ? ' style="width:' . $this->column_width[$column] . '"' : '';
            $output .= '<th scope="col"' . $width . '>' . $field->value . '</th>';
        }
        $output .= '</tr></thead><tbody>';
        foreach ($this->table_row as $rowIndex => $row) {
            if ($rowIndex === 0) continue;
            $output .= '<tr>';
            foreach ($row->fields as $column => $field) {
                $output .= '<td ' . ($this->cell_attr[$rowIndex][$column] ?? '') . '>' . $field->value . '</td>';
            }
            $output .= '</tr>';
        }
        return $output . '</tbody></table>';
    }
}
