<?php
/**
 * GettingStarted Plugin.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package GettingStarted
 */

/**
 * Class GettingStartedPlugin
 *
 * This plugin should:
 * 1. Display 5 tips for getting started on the dashboard
 * 2. Check off each item as it is completed
 * 3. Disable itself when "dismiss" is clicked
 */
class GettingStartedPlugin extends Gdn_Plugin {

    /**
     * Adds a "My Forums" menu option to the dashboard area.
     */
    public function settingsController_render_before($sender) {
        // Have they visited their dashboard?
        if (strtolower($sender->RequestMethod) != 'index') {
            $this->saveStep('Plugins.GettingStarted.Dashboard');
        }

        // Save the action if editing registration settings
        if (strcasecmp($sender->RequestMethod, 'registration') == 0 && $sender->Form->authenticatedPostBack() === true) {
            $this->saveStep('Plugins.GettingStarted.Registration');
        }

        // Save the action if they reviewed plugins
        if (strcasecmp($sender->RequestMethod, 'plugins') == 0) {
            $this->saveStep('Plugins.GettingStarted.Plugins');
        }

        // Save the action if they reviewed plugins
        if (strcasecmp($sender->RequestMethod, 'managecategories') == 0) {
            $this->saveStep('Plugins.GettingStarted.Categories');
        }

        // Add messages & their css on dashboard
        if (strcasecmp($sender->RequestMethod, 'index') == 0) {
            $sender->addCssFile('getting-started.css', 'plugins/GettingStarted');

            $session = Gdn::session();
            $welcomeMessage = '<div class="GettingStarted">'
                .anchor('&times;', '/dashboard/plugin/dismissgettingstarted/'.$session->transientKey(), 'Dismiss')
                ."<h1>".t("Here's how to get started:")."</h1>"
                .'<ul>
        <li class="One'.(c('Plugins.GettingStarted.Dashboard', '0') == '1' ? ' Done' : '').'">
	    <strong>'.anchor(t('Welcome to your Dashboard'), 'settings').'</strong>
        <p>'.t(
            'This is the administrative dashboard for your new community.',
            'This is the administrative dashboard for your new community. Check out the configuration options to the side. From there you can configure how your community works. <b>By default, only users in the "Administrator" role can see this part of your community.</b>'
        ).'</p>
        </li>
        <li class="Two'.(c('Plugins.GettingStarted.Discussions', '0') == '1' ? ' Done' : '').'">
	    <strong>'.anchor(t("Where is your Community Forum?"), '/').'</strong>
        <p>'.formatString(t(
            'Access your community forum by clicking the "Visit Site" link.',
            'Access your community forum by clicking the "Visit Site" link at the top of this page, or by <a href={/,url}>clicking here</a>. The community forum is what all of your users &amp; customers will see when they visit <a href="{/,url,domain}">{/,url,domain}</a>.'
        )).'</p>
        </li>
        <li class="Three'.(c('Plugins.GettingStarted.Categories', '0') == '1' ? ' Done' : '').'">
        <strong>'.anchor(t('Organize your Categories'), 'vanilla/settings/categories').'</strong>
        <p>'.t(
            'Categories are used to organize discussions.',
            'Categories are used to help your users organize their discussions in a way that is meaningful for your community.'
        ).'</p>
        </li>
        <li class="Four'.(c('Plugins.GettingStarted.Profile', '0') == '1' ? ' Done' : '').'">
        <strong>'.anchor(t('Customize your Public Profile'), 'profile').'</strong>
        <p>'.formatString(t(
            'Everyone who signs up gets a profile page.',
            'Everyone who signs up for your community gets a public profile page where they can upload a picture of themselves, manage their profile settings, and track cool things going on in the community. You should <a href="{/profile,url}">customize your profile now</a>.'
        )).'</p>
        </li>
        <li class="Five'.(c('Plugins.GettingStarted.Discussion', '0') == '1' ? ' Done' : '').'">
            <strong>'.anchor(t('Start your First Discussion'), 'post/discussion').'</strong>
	    <p>'.formatString(t(
            'Start the first discussion.',
            'Get the ball rolling in your community by <a href="{/post/discussion,url}">starting the first discussion</a> now.'
        )).'</p>
        </li>
        <li class="Six'.(c('Plugins.GettingStarted.Plugins', '0') == '1' ? ' Done' : '').'">
        <strong>'.anchor(t('Manage Plugins'), 'settings/plugins').'</strong>
        <p>'.t(
            'Change the way your community works with plugins.',
            'Change the way your community works with plugins. We\'ve bundled popular plugins with the software, and there are more available online.'
        ).'</p>
      </li>
   </ul>
</div>';
            $sender->addAsset('Messages', $welcomeMessage, 'WelcomeMessage');
        }
    }

    /**
     * Record when the various actions are taken.
     *
     * 1. If the user edits the registration settings.
     *
     * @param $step
     * @throws Exception
     */
    public function saveStep($step) {
        if (Gdn::config($step, '') != '1') {
            saveToConfig($step, '1');
        }

        // If all of the steps are now completed, disable this plugin
        if (Gdn::config('Plugins.GettingStarted.Registration', '0') == '1'
            && Gdn::config('Plugins.GettingStarted.Plugins', '0') == '1'
            && Gdn::config('Plugins.GettingStarted.Categories', '0') == '1'
            && Gdn::config('Plugins.GettingStarted.Profile', '0') == '1'
            && Gdn::config('Plugins.GettingStarted.Discussion', '0') == '1'
        ) {
            Gdn::pluginManager()->disablePlugin('GettingStarted');
        }
    }

    /**
     * If the user posts back any forms to their profile, they've completed step 4: profile customization.
     *
     * @param $sender
     */
    public function profileController_render_before($sender) {
        if (property_exists($sender, 'Form') && $sender->Form->authenticatedPostBack() === true) {
            $this->saveStep('Plugins.GettingStarted.Profile');
        }
    }

    /**
     * If the user starts a discussion, they've completed step 5: profile customization.
     *
     * @param $sender
     */
    public function postController_render_before($sender) {
        if (strcasecmp($sender->RequestMethod, 'discussion') == 0 && $sender->Form->authenticatedPostBack() === true) {
            $this->saveStep('Plugins.GettingStarted.Discussion');
        }
    }

    /**
     *
     *
     * @param $sender
     * @throws Exception
     */
    public function pluginController_dismissGettingStarted_create($sender) {
        Gdn::pluginManager()->disablePlugin('GettingStarted');
        echo 'TRUE';
    }

    /**
     *
     */
    public function setup() {
        // No setup required.
    }
}
