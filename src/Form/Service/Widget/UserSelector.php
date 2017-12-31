<?php
namespace Concrete\Core\Form\Service\Widget;

use Concrete\Core\Permission\Checker;
use Loader;
use URL;
use UserInfo;

class UserSelector
{
    /**
     * Build the HTML to be placed in a page to choose a user using a popup dialog.
     *
     * @param string $fieldName the name of the field
     * @param int|false $uID the ID of the user to be initially selected
     *
     * @return string
     *
     * @example
     * <code>
     *     $userSelector->selectUser('userID', USER_SUPER_ID); // prints out the admin user and makes it changeable.
     * </code>.
     */
    public function selectUser($fieldName, $uID = false)
    {
        $v = \View::getInstance();
        $v->requireAsset('core/users');
        $permissions = new Checker();

        $selectedUID = 0;
        if (isset($_REQUEST[$fieldName])) {
            $selectedUID = (int) $_REQUEST[$fieldName];
        } else {
            if ($uID > 0) {
                $selectedUID = $uID;
            }
        }

        if ($selectedUID) {
            $args = "{'inputName': '{$fieldName}', 'uID': {$selectedUID}}";
        } else {
            $args = "{'inputName': '{$fieldName}'}";
        }

        $identifier = new \Concrete\Core\Utility\Service\Identifier();
        $identifier = $identifier->getString(32);

        if ($permissions->canAccessUserSearch()) {
            $html = <<<EOL
            <div data-user-selector="{$identifier}"></div>
            <script type="text/javascript">
            $(function() {
                $('[data-user-selector={$identifier}]').concreteUserSelector({$args});
            });
            </script>
EOL;
        } else {
            // Read only
            $ui = false;
            if ($selectedUID) {
                $ui = UserInfo::getByID($selectedUID);
            }

            if (is_object($ui)) {
                $uName = $ui->getUserDisplayName();
                $uAvatar = $ui->getUserAvatar()->getPath();
            } else {
                $uName = t('(None Selected)');
                $uAvatar = \Config::get('concrete.icons.user_avatar.default');
            }

            $html = <<<EOL
            <div class="ccm-item-selector">
            <div class="ccm-item-selector-item-selected">
                <input type="hidden" name="{$fieldName}" value="{$selectedUID}">
                <div class="ccm-item-selector-item-selected-thumbnail">
                   <img src="{$uAvatar}" alt="admin" class="u-avatar">
               </div>
               <div class="ccm-item-selector-item-selected-title">{$uName}</div>
           </div>
           </div>
EOL;
        }

        return $html;
    }

    /**
     * Build the HTML to be placed in a page to choose a user using a select with users pupulated dynamically with ajax requests.
     *
     * @param string $fieldName the name of the field
     * @param int|false $uID the ID of the user to be initially selected
     * @param array $miscFields additional fields appended to the hidden input element (a hash array of attributes name => value), possibly including 'class'
     *
     * @return string
     *
     * @example
     * <code>
     *     $userSelector->quickSelect('userID', USER_SUPER_ID); // prints out the admin user and makes it changeable.
     * </code>.
     */
    public function quickSelect($fieldName, $uID = false, $miscFields = [])
    {
        $v = \View::getInstance();
        $v->requireAsset('selectize');
        $form = Loader::helper('form');
        $valt = Loader::helper('validation/token');
        $token = $valt->generate('quick_user_select_' . $fieldName);

        $selectedUID = 0;
        if (isset($_REQUEST[$fieldName])) {
            $selectedUID = $_REQUEST[$fieldName];
        } else {
            if ($uID > 0) {
                $selectedUID = $uID;
            }
        }

        $uName = '';
        if ($selectedUID > 0) {
            $ui = UserInfo::getByID($selectedUID);
            $uName = $ui->getUserDisplayName();
        }

        $html = "
		<script type=\"text/javascript\">
		$(function () {
			$('.ccm-quick-user-selector input').unbind().selectize({
                valueField: 'value',
                labelField: 'label',
                searchField: ['label'],";

        if ($uID) {
            $html .= "options: [{'label': '" . h($uName) . "', 'value': " . (int) $selectedUID . '}],
				items: [' . (int) $selectedUID . '],';
        }

        $html .= "maxItems: 1,
                load: function(query, callback) {
                    if (!query.length) return callback();
                    $.ajax({
                        url: '" . REL_DIR_FILES_TOOLS_REQUIRED . '/users/autocomplete?key=' . $fieldName . '&token=' . $token . "&term=' + encodeURIComponent(query),
                        type: 'GET',
						dataType: 'json',
                        error: function() {
                            callback();
                        },
                        success: function(res) {
                            callback(res);
                        }
                    });
                }
		    });
		});
		</script>";
        $html .= '<span class="ccm-quick-user-selector">' . $form->hidden($fieldName, '', $miscFields) . '</span>';

        return $html;
    }

    /**
     * Build the HTML to be placed in a page to choose multiple users using a popup dialog.
     *
     * @param string $fieldName the name of the field
     * @param \Concrete\Core\Entity\User\User[]|\Concrete\Core\User\UserInfo[]\Traversable $users The users to be initially selected
     *
     * @return string
     */
    public function selectMultipleUsers($fieldName, $users = [])
    {
        $html = '';
        $html .= '<table id="ccmUserSelect' . $fieldName . '" class="table table-condensed" cellspacing="0" cellpadding="0" border="0">';
        $html .= '<tr>';
        $html .= '<th>' . t('Username') . '</th>';
        $html .= '<th>' . t('Email Address') . '</th>';
        $html .= '<th style="width: 1px"><a class="icon-link ccm-user-select-item dialog-launch" dialog-append-buttons="true" dialog-width="90%" dialog-height="70%" dialog-modal="false" dialog-title="' . t('Choose User') . '" href="' . URL::to('/ccm/system/dialogs/user/search') . '"><i class="fa fa-plus-circle" /></a></th>';
        $html .= '</tr><tbody id="ccmUserSelect' . $fieldName . '_body" >';
        foreach ($users as $ui) {
            $html .= '<tr id="ccmUserSelect' . $fieldName . '_' . $ui->getUserID() . '" class="ccm-list-record">';
            $html .= '<td><input type="hidden" name="' . $fieldName . '[]" value="' . $ui->getUserID() . '" />' . $ui->getUserName() . '</td>';
            $html .= '<td>' . $ui->getUserEmail() . '</td>';
            $html .= '<td><a href="javascript:void(0)" class="ccm-user-list-clear icon-link"><i class="fa fa-minus-circle ccm-user-list-clear-button"></i></a>';
            $html .= '</tr>';
        }
        if (count($users) == 0) {
            $html .= '<tr class="ccm-user-selected-item-none"><td colspan="3">' . t('No users selected.') . '</td></tr>';
        }
        $html .= '</tbody></table><script type="text/javascript">
		$(function() {
			$("#ccmUserSelect' . $fieldName . ' .ccm-user-select-item").dialog();
			$("a.ccm-user-list-clear").click(function() {
				$(this).parents(\'tr\').remove();
			});

			$("#ccmUserSelect' . $fieldName . ' .ccm-user-select-item").on(\'click\', function() {
				ConcreteEvent.subscribe(\'UserSearchDialogSelectUser\', function(e, data) {
					var uID = data.uID, uName = data.uName, uEmail = data.uEmail;
					e.stopPropagation();
					$("tr.ccm-user-selected-item-none").hide();
					if ($("#ccmUserSelect' . $fieldName . '_" + uID).length < 1) {
						var html = "";
						html += "<tr id=\"ccmUserSelect' . $fieldName . '_" + uID + "\" class=\"ccm-list-record\"><td><input type=\"hidden\" name=\"' . $fieldName . '[]\" value=\"" + uID + "\" />" + uName + "</td>";
						html += "<td>" + uEmail + "</td>";
						html += "<td><a href=\"javascript:void(0)\" class=\"ccm-user-list-clear icon-link\"><i class=\"fa fa-minus-circle ccm-user-list-clear-button\" /></a>";
						html += "</tr>";
						$("#ccmUserSelect' . $fieldName . '_body").append(html);
					}
					$("a.ccm-user-list-clear").click(function() {
						$(this).parents(\'tr\').remove();
					});
				});
				ConcreteEvent.subscribe(\'UserSearchDialogAfterSelectUser\', function(e) {
					jQuery.fn.dialog.closeTop();
				});
			});
		});

		</script>';

        return $html;
    }
}
