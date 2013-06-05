<?php

namespace Starch\Core;

/**
 * A controller for JSON interfaces
 * @package default
 */
class APIController extends Controller
{
    protected $response;

    /**
     * Outputs $this->response as JSON
     * @return void
     */
    public function display()
    {
        header('Content-type: application/json');
        echo json_encode($this->response);
    }
}