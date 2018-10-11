<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once __DIR__ . "/../vendor/autoload.php";

use srag\DIC\DICTrait;

/**
 * Class ilUserTakeOverUIHookGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @author  Martin Studer <ms@studer-raimann.ch>
 * @version $Id$
 * @ingroup ServicesUIComponent
 */
class ilUserTakeOverUIHookGUI extends ilUIHookPluginGUI {

	use DICTrait;

	const PLUGIN_CLASS_NAME = ilUserTakeOverPlugin::class;

	/**
	 * @var array
	 */
	protected static $loaded = [];


	/**
	 * @param string $key
	 *
	 * @return bool
	 *
	 */
	protected static function isLoaded($key) {
		return self::$loaded[$key] == 1;
	}


	/**
	 * @param string $key
	 */
	protected static function setLoaded($key) {
		self::$loaded[$key] = 1;
	}


	/**
	 * @var int
	 */
	protected static $num = 0;


	/**
	 * @param string $a_comp
	 * @param string $a_part
	 * @param array  $a_par
	 *
	 * @return array
	 */
	public function getHTML($a_comp, $a_part, $a_par = []) {
		if ($a_comp == 'Services/MainMenu' && $a_part == 'main_menu_search') {
			if (!self::isLoaded('user_take_over')) {
				$html = '';
				/////////////////// FOR EXITING THE VIEW ///////////////////////
				if ($_SESSION[usrtoHelper::USR_ID_BACKUP]) {
					$html .= $this->takeBackHtml();
				}

				/////////// For the Demo Group //////////////////
				/// // TODO use group code
				/*				if (in_array(self::dic()->user()->getId(), $config->getDemoGroup())) {
									$html .= $this->getDemoGroupHtml($config, self::dic()->user());
								}*/

				// If we are admin
				/** Some Async requests wont instanciate rbacreview. Thus we just terminate. */
				if ((self::dic()->rbacreview() instanceof ilRbacReview)
					&& in_array(2, self::dic()->rbacreview()->assignedGlobalRoles(self::dic()->user()->getId()))) {
					///////////////// IN THE USER ADMINISTRATION /////////////////
					$this->initTakeOverToolbar(self::dic()->toolbar());

					/*if (!in_array(self::dic()->user()->getId(), $config->getDemoGroup())) //////////////TOP BAR /////////////
					{*/
					$html .= $this->getTopBarHtml();
					//}
				}

				self::setLoaded('user_take_over'); // Main Menu gets called multiple times so we statically save that we already did all that is needed.

				return [ "mode" => ilUIHookPluginGUI::PREPEND, "html" => $html ];
			} else {
				return [ 'mode' => ilUIHookPluginGUI::KEEP, "html" => '' ];
			}
		}
	}


	public function gotoHook() {
		if (preg_match("/usr_takeover_(.*)/uim", filter_input(INPUT_GET, 'target'), $matches)) {
			$track = (int)filter_input(INPUT_GET, 'track');
			usrtoHelper::getInstance()->takeOver((int)$matches[1], $track === 1);
		}
		if (preg_match("/usr_takeback/uim", filter_input(INPUT_GET, 'target'), $matches)) {
			usrtoHelper::getInstance()->switchBack();
		}
	}


	/**
	 * @return array
	 * @internal param $a_comp
	 */
	protected function getTopBarHtml() {
		$template = self::plugin()->getPluginObject()->getTemplate("tpl.MMUserTakeOver.html", false, false);
		$template->setVariable("TXT_TAKE_OVER_USER", self::plugin()->translate("take_over_user"));
		$template->setVariable("SEARCHUSERLINK", self::dic()->ctrl()->getLinkTargetByClass([
			ilUIPluginRouterGUI::class,
			//ilUserTakeOverConfigGUI::class,
			ilUserTakeOverMembersGUI::class
		], ilUserTakeOverMembersGUI::CMD_SEARCH_USERS));
		// If we already switched user we want to set the backup id to the new takeover but keep the one to the original user.
		if (!$_SESSION[usrtoHelper::USR_ID_BACKUP]) {
			$track = 1;
		} else {
			$track = 0;
		}
		$template->setVariable("TAKEOVERPREFIX", "goto.php?track=$track&target=usr_takeover_");
		$template->setVariable("LOADING_TEXT", self::plugin()->translate("loading"));
		$template->setVariable("NO_RESULTS", self::plugin()->translate("no_results"));
		self::setLoaded('user_take_over');
		$html = $template->get();

		$html = '<li>' . $html . '</li>';

		return $html;
	}


	/**
	 * @param ilUserTakeOverConfig $config
	 * @param ilObjUser            $ilUser
	 *
	 * @return string
	 */
	protected function getDemoGroupHtml($config, $ilUser) {
		$inner_html = "";
		foreach ($config->getDemoGroup() as $userId) {
			$user = new ilObjUser($userId);
			$b = "";
			if ($userId == $ilUser->getId()) {
				$b = " style='font-weight: bold; margin-left: -33px;'><span class=\"glyphicon glyphicon-hand-right\">&nbsp;</span";
			}
			$inner_html .= "<li style=\"padding-left: 38px;\">
								<a href=\"goto.php?track=0&target=usr_takeover_$userId\"$b>{$user->getPresentationTitle()}</a>
							</li>";
		}
		$tmpHtml = "<a href='#' class='dropdown-toggle' data-toggle='dropdown' title='{$ilUser->getPresentationTitle()}'><span class='glyphicon glyphicon-eye-open'><span class='caret'></span></span>
							</a>
							<ul class=\"dropdown-menu pull-right\" role=\"menu\">
							$inner_html
						</ul>";

		$tmpHtml = '<li>' . $tmpHtml . '</li>';

		return $tmpHtml;
	}


	/**
	 * @param ilToolbarGUI $ilToolbar
	 *
	 * @return mixed
	 */
	protected function initTakeOverToolbar($ilToolbar) {
		if (strcasecmp(filter_input(INPUT_GET, 'cmdClass'), ilObjUserGUI::class) == 0 AND (filter_input(INPUT_GET, 'cmd') == 'view' OR filter_input(INPUT_GET, 'cmd') == 'edit')) {
			if ($ilToolbar instanceof ilToolbarGUI) {
				$link = 'goto.php?track=1&target=usr_takeover_' . filter_input(INPUT_GET, 'obj_id');
				$button = ilLinkButton::getInstance();
				$button->setCaption(self::plugin()->translate('take_over_user_view'), false);
				$button->setUrl($link);
				$ilToolbar->addButtonInstance($button);

				return $ilToolbar;
			}

			return $ilToolbar;
		}

		return $ilToolbar;
	}


	private function takeBackHtml() {

		$ilToolbar = new ilToolbarGUI();

		/**
		 * @var ilPluginAdmin $ilPluginAdmin
		 */
		if ($ilToolbar instanceof ilToolbarGUI) {

			$link = 'goto.php?target=usr_takeback';

			/**
			 * @author Jean-Luc Braun <braun@qualitus.de>
			 */
			$tmpHtml = '<a class="dropdown-toggle" id="leave_user_view" target="" href="' . $link . '"><span class="glyphicon glyphicon-eye-close"></span></a>';

			//$tmpHtml = '<li>' . $tmpHtml . '</li>';

			return $tmpHtml;
		}

		return '';
	}
}