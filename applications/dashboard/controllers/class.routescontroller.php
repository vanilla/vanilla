<?php
/**
 * Controlling default routes in Garden's MVC dispatcher system.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /routes endpoint.
 */
class RoutesController extends DashboardController {

    /** @var array Models to automatically instantiate. */
    public $Uses = ['Form'];

    /**
     * Set menu path. Automatically run on every use.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize() {
        parent::initialize();
        Gdn_Theme::section('Dashboard');
        if ($this->Menu) {
            $this->Menu->highlightRoute('/dashboard/settings');
        }
    }

    /**
     * Create a route.
     *
     * @since 2.0.0
     * @access public
     */
    public function add() {
        $this->permission('Garden.Settings.Manage');
        // Use the edit form with no roleid specified.
        $this->View = 'Edit';
        $this->edit();
    }

    /**
     * Edit a route.
     *
     * @since 2.0.0
     * @access public
     * @param string $routeIndex Name of route.
     */
    public function edit($routeIndex = false) {
        $this->permission('Garden.Settings.Manage');
        $this->setHighlightRoute('dashboard/routes');
        $this->Route = Gdn::router()->getRoute($routeIndex);

        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField(['Route', 'Target', 'Type']);

        // Set the model on the form.
        $this->Form->setModel($configurationModel);

        // If seeing the form for the first time...
        if (!$this->Form->authenticatedPostBack()) {
            // Apply the route info to the form.
            if ($this->Route !== false) {
                $this->Form->setData([
                    'Route' => $this->Route['Route'],
                    'Target' => $this->Route['Destination'],
                    'Type' => $this->Route['Type']
                ]);
            }
        } else {
            // Define some validation rules for the fields being saved
            $configurationModel->Validation->applyRule('Route', 'Required');
            $configurationModel->Validation->applyRule('Target', 'Required');
            $configurationModel->Validation->applyRule('Type', 'Required');

            // Validate & Save
            $formPostValues = $this->Form->formValues();

            // Dunno.
            if ($this->Route['Reserved']) {
                $formPostValues['Route'] = $this->Route['Route'];
            }

            if ($configurationModel->validate($formPostValues)) {
                $newRouteName = val('Route', $formPostValues);

                if ($this->Route !== false && $newRouteName != $this->Route['Route']) {
                    Gdn::router()->deleteRoute($this->Route['Route']);
                }

                Gdn::router()->setRoute(
                    $newRouteName,
                    val('Target', $formPostValues),
                    val('Type', $formPostValues)
                );

                $this->informMessage(t("The route was saved successfully."));
                $this->setRedirectTo('dashboard/routes');
            } else {
                $this->Form->setValidationResults($configurationModel->validationResults());
            }
        }

        $this->render();
    }

    /**
     * Remove a route.
     *
     * @since 2.0.0
     * @access public
     * @param mixed $routeIndex Name of route.
     * @param string $transientKey Security token.
     */
    public function delete($routeIndex = false, $transientKey = false) {
        $this->permission('Garden.Settings.Manage');
        $this->deliveryType(DELIVERY_TYPE_BOOL);
        $session = Gdn::session();

        // If seeing the form for the first time...
        if ($transientKey !== false && $session->validateTransientKey($transientKey)) {
            Gdn::router()->deleteRoute($routeIndex);
        }

        if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            redirectTo('dashboard/routes');
        }

        $this->render();
    }

    /**
     * Show list of current routes.
     *
     * @since 2.0.0
     * @access public
     */
    public function index() {
        $this->permission('Garden.Settings.Manage');
        $this->setHighlightRoute('dashboard/routes');
        $this->title(t('Routes'));

        $this->MyRoutes = Gdn::router()->Routes;
        $this->render();
    }
}
