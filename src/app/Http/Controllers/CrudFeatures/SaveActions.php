<?php

namespace Backpack\CRUD\app\Http\Controllers\CrudFeatures;

//save_and_back save_and_edit save_and_new
trait SaveActions
{
    /**
     * Get the save configured save action or the one stored in a session variable.
     * @return [type] [description]
     */
    public function getSaveAction()
    {
        $saveAction = session('save_action', config('backpack.crud.default_save_action', 'save_and_back'));
        $saveOptions = $this->crud->actions;
        
        if (!isset($saveOptions[$saveAction])) {
            $saveAction = config('backpack.crud.default_save_action', 'save_and_back');
        }
        
        $saveCurrent = [
            'value' => $saveAction,
            'label' => $this->getSaveActionButtonName($saveAction, $saveOptions[$saveAction]),
        ];

        foreach ($saveOptions as $key => $value) {
            if ($saveAction == $key) {
                unset($saveOptions[$key]);
            } else {
                $saveOptions[$key] = $this->getSaveActionButtonName($key, $value);
            }
        }

        return [
            'active' => $saveCurrent,
            'options' => $saveOptions,
        ];
    }

    /**
     * Change the session variable that remembers what to do after the "Save" action.
     * @param [type] $forceSaveAction [description]
     */
    public function setSaveAction($forceSaveAction = null)
    {
        if ($forceSaveAction) {
            $saveAction = $forceSaveAction;
        } else {
            $saveAction = \Request::input('save_action', config('backpack.crud.default_save_action', 'save_and_back'));
        }

        if (session('save_action', 'save_and_back') !== $saveAction && config('backpack.crud.show_save_action_change', true)) {
            \Alert::info(trans('backpack::crud.save_action_changed_notification'))->flash();
        }

        session(['save_action' => $saveAction]);
    }

    /**
     * Redirect to the correct URL, depending on which save action has been selected.
     * @param  [type] $itemId [description]
     * @return [type]         [description]
     */
    public function performSaveAction($itemId = null)
    {
        $saveAction = \Request::input('save_action', config('backpack.crud.default_save_action', 'save_and_back'));
        $itemId = $itemId ? $itemId : \Request::input('id');

        switch ($saveAction) {
            case 'save_and_new':
                $redirectUrl = $this->crud->route.'/create';
                break;
            case 'save_and_edit':
                $redirectUrl = $this->crud->route.'/'.$itemId.'/edit';
                if (\Request::has('locale')) {
                    $redirectUrl .= '?locale='.\Request::input('locale');
                }
                break;
            case 'save_and_back':
                $redirectUrl = $this->crud->route;
                break;
            default:
                $redirectUrl = $this->crud->actions_url[$saveAction];
        }

        return \Redirect::to($redirectUrl);
    }

    /**
     * Get the translated text for the Save button.
     * @param  string $actionKey [description]
     * @return [type]              [description]
     */
    private function getSaveActionButtonName($actionKey = 'save_and_back', $label = 'backpack::crud.save_action_save_and_back')
    {
        switch ($actionKey) {
            case 'save_and_edit':
                return trans('backpack::crud.save_action_save_and_edit');
                break;
            case 'save_and_new':
                return trans('backpack::crud.save_action_save_and_new');
                break;
            case 'save_and_back':
                return trans('backpack::crud.save_action_save_and_back');
                break;
            default:
                return trans($label);
        }
    }
}
