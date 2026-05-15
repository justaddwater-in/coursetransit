<?php
namespace CourseTransit\Controllers;

class BaseController
{
    protected function render($view, $data = [])
    {
        extract($data, EXTR_SKIP);

        require COURSETRANSIT_PATH . 'app/Views/layout.php';
    }
}
