<?php

class CropImageModule extends Gdn_Module {

    private $sender;
    public $form;
    private $width;
    private $height;
    private $source;

    private $currentCropUrl;
    private $sourceImageUrl;

    public $saveButton = true;

    public function __construct($sender, $form, $width, $height, $source) {
        $this->sender = $sender;
        $this->form = $form;
        $this->width = $width;
        $this->height = $height;
        $this->source = $source;

        $this->addHiddenFields($this->form, $this->width, $this->height, $this->source);
        $this->addAssets($this->sender);
    }

    public function isCropped() {
        return $this->form->getValue('w') > 0 && $this->form->getValue('h') > 0;
    }

    public function setCurrentCroppedImageUrl($url) {
        $this->currentCropUrl = $url;
    }

    public function setSourceImageUrl($url) {
        $this->sourceImageUrl = $url;
    }

    public function getCurrentCroppedImageUrl() {
        return $this->currentCropUrl;
    }

    public function getHeight() {
        return $this->height;
    }

    public function getWidth() {
        return $this->width;
    }

    public function getSourceImageUrl() {
        return $this->sourceImageUrl;
    }

    public function setSource($source) {
        $this->source = $source;
        $this->updateHiddenSource($this->form, $this->source);
    }

    public function setSize($width, $height) {
        if ($width <= 0 || $height <= 0) {
            return false;
        }
        $this->width = $width;
        $this->height = $height;
        $this->updateHiddenSize($this->form, $this->width, $this->height);
    }

    public function getCropValues() {
        $x = $this->form->getValue('x');
        $y = $this->form->getValue('y');
        $w = $this->form->getValue('w');
        $h = $this->form->getValue('h');
        return array('x' => $x,
            'y' => $y,
            'width' => $w,
            'height' => $h);
    }

    public function getCropHeight() {
        return $this->form->getValue('h');
    }

    public function getCropWidth() {
        return $this->form->getValue('w');
    }

    public function getCropXValue() {
        return $this->form->getValue('x');
    }

    public function getCropYValue() {
        return $this->form->getValue('y');
    }

    private function addHiddenFields($form, $width, $height, $source) {
        // JS-manipulated values
        $form->addHidden('x', '0');
        $form->addHidden('y', '0');
        $form->addHidden('w', '0');
        $form->addHidden('h', '0');

        // Constants
        $sourceSize = getimagesize($source);
        $form->addHidden('WidthSource', $sourceSize[0]);
        $form->addHidden('HeightSource', $sourceSize[1]);
        $form->addHidden('CropSizeWidth', $width);
        $form->addHidden('CropSizeHeight', $height);
    }

    private function updateHiddenSize($form, $width, $height) {
        $form->addHidden('CropSizeWidth', $width, true);
        $form->addHidden('CropSizeHeight', $height, true);
    }

    private function updateHiddenSource($form, $source) {
        $sourceSize = getimagesize($source);
        $form->addHidden('WidthSource', $sourceSize[0], true);
        $form->addHidden('HeightSource', $sourceSize[1], true);
    }

    private function addAssets($sender) {
        $sender->addJsFile('jquery.jcrop.min.js');
        $sender->addJsFile('cropimage.js');
        $sender->addCssFile('cropimage.css');
    }
}
