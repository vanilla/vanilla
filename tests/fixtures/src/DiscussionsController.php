<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Fixtures;

use Garden\Web\RequestInterface;

class DiscussionsController {
    public function index($page = '') {

    }

    public function index_foo() {

    }

    public function get($id) {

    }

    public function get_recent(array $query) {

    }

    public function get_bookmarked($page = '') {

    }

    public function get_edit($id) {

    }

    public function post(array $body) {

    }

    public function patch($id, array $data) {

    }

    public function patch_pin($id, array $body) {

    }

    public function patch_bookmarked($id, array $body) {

    }

    public function delete($id) {

    }

    public function get_delete($id) {

    }

    public function foo() {

    }

    public function get_search(RequestInterface $request) {

    }
    public function post_search(Request $request) {

    }

    public function get_me(DiscussionsController $sender, $foo) {

    }

    public function get_help($id, ...$parts) {
        return $parts;
    }

    public function getSomething() {

    }

    public function setSomething($val) {
    }

    public function iAmPublic() {

    }

    public function isPublic($method) {
        return strcasecmp($method, 'iAmPublic') === 0;
    }

    public function post_noMap($query, $body, $data) {

    }

    public function get_path1($path = '') {

    }

    public function get_path2($a, $path = '') {

    }

    public function get_path3($path, $b) {

    }

    public function get_path4($a, $path, $b) {

    }

    public function get_article($path, $page = '') {
    }
}
