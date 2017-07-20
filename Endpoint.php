<?php

namespace Vanilla;

abstract class Endpoint {

    /**
     * Action name to be handled
     *
     * @var string
     */
    protected $name;

    /**
     * Check if Endpoint will be accessible only by authorized users
     *
     * @var bool
     */
    protected $authOnly = false;

    /**
     * Endpoint handler
     *
     * @return mixed
     */
    abstract public function handle();

    /**
     * Check if user is authorized to perform the action
     *
     * @return mixed
     */
    protected function authorized()
    {
        return true;
    }

    public function name()
    {
        return $this->name;
    }

    /**
     * Handle the request if authorized to
     */
    public function handler() {
        if(($this->authOnly && !is_user_logged_in()) || !$this->authorized()) {
            wp_send_json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $this->handle();
    }
}
