<?php

/**
 * @copyright 2010-2014 Vanilla Forums Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 */

// Define the plugin:
$PluginInfo['CloudflareSupport'] = array(
   'Description' => 'This plugin modifies the Request object to work with Cloudflare.',
   'Version' => '1.2.2',
   'RequiredApplications' => array('Vanilla' => '2.1'),
   'RequiredTheme' => FALSE,
   'RequiredPlugins' => FALSE,
   'HasLocale' => FALSE,
   'SettingsUrl' => FALSE,
   'SettingsPermission' => 'Garden.Settings.Manage',
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

/**
 * Cloudflare Support Plugin
 *
 * This plugin inspect the incoming request headers and applies CF-Connecting-IP
 * to the request object.
 *
 * Changes:
 *  1.0     Initial release
 *  1.2     Fix bad method call
 *  1.2.1   Fix bad method call again
 *  1.2.2   Update CF IP list
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package Addons
 */
class CloudflareSupportPlugin extends Gdn_Plugin {

   // CloudFlare IP ranges listed at https://www.cloudflare.com/ips
   protected $CloudflareSourceIPs = array(
      "199.27.128.0/21",
      "173.245.48.0/20",
      "103.21.244.0/22",
      "103.22.200.0/22",
      "103.31.4.0/22",
      "141.101.64.0/18",
      "108.162.192.0/18",
      "190.93.240.0/20",
      "188.114.96.0/20",
      "197.234.240.0/22",
      "198.41.128.0/17",
      "162.158.0.0/15",
      "104.16.0.0/12"
    );

   public function __construct() {
      parent::__construct();

      // If cloudflare isn't telling us a client IP, bust outta here!
      $CloudflareClientIP = val('HTTP_CF_CONNECTING_IP', $_SERVER, NULL);
      if (is_null($CloudflareClientIP)) {
         return;
      }

      $RequestAddress = Gdn::Request()->RequestAddress();
      $CloudflareRequest = FALSE;
      foreach ($this->CloudflareSourceIPs as $CloudflareIPRange) {

         // Not a cloudflare origin server
         if (!ip_in_range($RequestAddress, $CloudflareIPRange)) {
            continue;
         }

         Gdn::Request()->RequestAddress($CloudflareClientIP);
         $CloudflareRequest = TRUE;
         break;
      }

      // Let people know that the CF plugin is turned on.
      if ($CloudflareRequest && !headers_sent()) {
         header("X-CF-Powered-By: CF-Vanilla v" . $this->GetPluginKey('Version'));
      }
   }

}