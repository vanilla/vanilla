<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\ValidationException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Dashboard\Models\RoleRequestModel;
use Vanilla\Dashboard\Models\RoleRequestsApiController;
use Vanilla\Exception\FeatureNotEnabledException;
use Vanilla\Utility\ArrayUtils;

/**
 * This controller is meant for
 */
class RequestsController extends \Gdn_Controller {
    /**
     * @var RoleRequestsApiController
     */
    private $roleRequests;

    /**
     * @var \RoleModel
     */
    private $roleModel;

    /**
     * @var \Gdn_Form
     */
    public $form;

    /**
     * RequestsController constructor.
     *
     * @param RoleRequestsApiController $roleRequests
     * @param \RoleModel $roleModel
     */
    public function __construct(
        RoleRequestsApiController $roleRequests,
        \RoleModel $roleModel
    ) {
        parent::__construct();
        $this->roleRequests = $roleRequests;
        $this->roleModel = $roleModel;
        $this->form = new \Gdn_Form();
    }

    /**
     * Include JS and CSS used by all methods.
     *
     * Always called by dispatcher before controller's requested method.
     */
    public function initialize() {
        $this->Head = new \HeadModule($this);

        $this->addJsFile('jquery.js');
        $this->addJsFile('jquery.form.js');
        $this->addJsFile('jquery.popup.js');
        $this->addJsFile('jquery.gardenhandleajaxform.js');
        $this->addJsFile('global.js');

        $this->addCssFile('style.css');
        $this->addCssFile('vanillicon.css', 'static');

        $this->Head->addTag('meta', ['name' => 'robots', 'content' => 'noindex']);
        $this->CssClass .= ' NoPanel Entry';
        parent::initialize();

        \Gdn_Theme::section('Entry');
    }

    /**
     * {@inheritDoc}
     */
    public function renderData($data = null) {
        throw new \Garden\Web\Exception\ForbiddenException("Use the API to view request data.");
    }

    /**
     * Handle the `/requests/role-applications` endpoint.
     *
     * @param int|string $role The role ID or name.
     * @throws Gdn_UserException Throw user errors.
     * @throws NotFoundException No roleID or invalid roleID.
     * @throws FeatureNotEnabledException Restrict feature to instances with the flag in the config.
     */
    public function roleApplications($role): void {
        \Vanilla\FeatureFlagHelper::ensureFeature(ManageController::FEATURE_ROLE_APPLICATIONS);
        $this->permission('Garden.SignIn.Allow');

        $role = $this->roleModel->getWhere([(is_numeric($role) ? 'RoleID' : 'Name') => $role])->firstRow(DATASET_TYPE_ARRAY);
        if (!$role) {
            throw new NotFoundException('Role');
        }
        $role = ArrayUtils::camelCase($role);
        $roleID = (int)$role['roleID'];

        // Grab the application meta.
        $meta = $this->roleRequests->get_metas(RoleRequestModel::TYPE_APPLICATION, $roleID)->getData();
        $this->setData('roleRequestMeta', $meta);

        if (in_array($roleID, Gdn::userModel()->getRoleIDs(Gdn::session()->UserID))) {
            $state = 'hasRole';
        } else {
            // Grab an existing application.
            $application = $this->roleRequests->index_applications([
                    'userID' => \Gdn::session()->UserID,
                    'roleID' => $roleID,
                    'type' => RoleRequestModel::TYPE_APPLICATION,
                ])->getData()[0] ?? null;
            $this->setData('roleRequest', $application);
            $canReApply =  (!empty($meta['attributes']['allowReapply']) && $application['status'] === RoleRequestModel::STATUS_DENIED);
            if (!empty($application) && !$canReApply) {
                $state = 'alreadyApplied';
            } else {
                $state = 'apply';
                if ($this->Request->isAuthenticatedPostBack(true)) {
                    try {
                        $data = [
                            'roleID' => $roleID,
                            'type' => RoleRequestModel::TYPE_APPLICATION,
                            'attributes' => $this->form->getFormValue('attributes', []),
                        ];
                        $this->roleRequests->post_applications($data);
                        $state = 'success';
                    } catch (\Exception $ex) {
                        $this->form->addError($ex->getMessage());
                    }
                }
            }
        }
        $this->setData('state', $state);
        $this->render('role-application');
    }
}
