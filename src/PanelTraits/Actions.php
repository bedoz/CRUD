<?php
namespace Backpack\CRUD\PanelTraits;

trait Actions {
    /**
     * Remove a save action
     */
    public function removeAction($action){
        $action = (array)$action;
        $action = array_flip($action);
        return $this->actions = array_diff_key($this->actions, $action);
    }
    
    public function addAction($action){
        $action = (array)$action;
        $action = array_flip($action);
        return $this->actions = array_merge($this->actions, $action);
    }
}