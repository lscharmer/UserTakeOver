<?php

use srag\DIC\UserTakeOver\DICTrait;

/**
 * Class ilUserTakeOverSettingsGUI
 *
 * @author Thibeau Fuhrer <thf@studer-raimann.ch>
 */
class ilUserTakeOverSettingsGUI
{
    use DICTrait;

    const PLUGIN_CLASS_NAME = ilUserTakeOverPlugin::class;

    /**
     * lang vars
     */
    const MSG_SUCCESS = 'success';
    const MSG_FAILURE = 'something_went_wrong';

    /**
     * commands
     */
    const CMD_STANDARD       = ilUserTakeOverConfigGUI::CMD_CONFIGURE;
    const CMD_CONFIG_SAVE    = 'saveConfig';
    const CMD_CANCEL         = 'cancel';

    /**
     * @throws ilException
     */
    public function executeCommand()
    {
        $cmd = self::dic()->ctrl()->getCmd(self::CMD_STANDARD);
        switch ($cmd) {
            case self::CMD_CONFIG_SAVE:
            case self::CMD_STANDARD:
            case self::CMD_CANCEL:
                $this->{$cmd}();
                break;
            default:
                throw new ilException("command not found");
        }
    }

    /**
     * displays the configuration form (legacy).
     */
    private function configure()
    {
        $form = new ilUserTakeOverSettingsFormGUI();
        self::plugin()->output($form->getHTML());
    }

    /**
     * stores the submitted configurations if valid, redirects back to index cmd.
     */
    private function saveConfig()
    {
        $form = new ilUserTakeOverSettingsFormGUI();
        if ($form->checkInput()) {
            ilUtil::sendSuccess(self::MSG_SUCCESS, true);
            $form->save();
        } else {
            ilUtil::sendSuccess(self::MSG_FAILURE, true);
        }

        $this->cancel();
    }

    /**
     * redirects back to the index cmd.
     */
    private function cancel()
    {
        self::dic()->ctrl()->redirectByClass(
            [ilUserTakeOverMainGUI::class, self::class],
            self::CMD_STANDARD
        );
    }
}