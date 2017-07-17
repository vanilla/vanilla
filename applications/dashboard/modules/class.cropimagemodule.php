<?php

/**
 * Class CropImageModule
 *
 * Plugs into a Gdn_Form object to add a image cropping field. Injects the necessary hidden fields into the form and adds
 * javascript and css assets to the controller. Provides helper functions for retrieving the cropped image's dimensions
 * and location. Values are easily plugged into the options param for Gdn_UploadImage::saveImageAs()
 */
class CropImageModule extends Gdn_Module {

    /**
     * @var Gdn_Form The form to insert the crop module into.
     */
    public $form;

    /**
     * @var int The width of the final cropped image.
     */
    private $width;

    /**
     * @var int The height of the final cropped image.
     */
    private $height;

    /**
     * @var string The path to the local copy of the image.
     */
    private $source;

    /**
     * @var string The URL to the existing cropped image.
     */
    private $existingCropUrl;

    /**
     * @var string The URL to the the existing source image.
     */
    private $sourceImageUrl;

    /**
     * @var bool Whether to include a save button for the form in the view.
     */
    public $saveButton = true;

    /**
     * Constructor
     *
     * Adds assets to the sender object and adds the required hidden fields to the form.
     *
     * @param object $sender The sending controller object.
     * @param bool $form The form to insert the crop module into.
     * @param int $width The width of the final cropped image.
     * @param int $height The height of the final cropped image.
     * @param string $source The path to the local copy of the image.
     */
    public function __construct($sender, $form, $width, $height, $source) {
        $this->form = $form;
        $this->width = $width;
        $this->height = $height;
        $this->source = $source;

        $this->addHiddenFields($this->form, $this->width, $this->height, $this->source);
        $this->addAssets($sender);
    }

    /**
     * Assesses whether any cropping has been applied to the image.
     *
     * @return bool Whether the image has been cropped.
     */
    public function isCropped() {
        return $this->form->getValue('w') > 0 && $this->form->getValue('h') > 0;
    }

    /**
     * @return int The width of the final cropped image.
     */
    public function getWidth() {
        return $this->width;
    }

    /**
     * @param int $width The width of the final cropped image.
     */
    private function setWidth($width) {
        $this->width = $width;
    }

    /**
     * @return int The height of the final cropped image.
     */
    public function getHeight() {
        return $this->height;
    }

    /**
     * @param int $height The height of the final cropped image.
     */
    private function setHeight($height) {
        $this->height = $height;
    }

    /**
     * @return string The URL to the existing cropped image.
     */
    public function getExistingCropUrl() {
        return $this->existingCropUrl;
    }

    /**
     * @param string $existingCropUrl The URL to the existing cropped image.
     */
    public function setExistingCropUrl($existingCropUrl) {
        $this->existingCropUrl = $existingCropUrl;
    }

    /**
     * @return string The URL to the existing source image.
     */
    public function getSourceImageUrl() {
        return $this->sourceImageUrl;
    }

    /**
     * @param string $sourceImageUrl The URL to the existing source image.
     */
    public function setSourceImageUrl($sourceImageUrl) {
        $this->sourceImageUrl = $sourceImageUrl;
    }

    /**
     * @param string $source The path to the local copy of the image.
     */
    public function setSource($source) {
        $this->source = $source;
        $this->updateHiddenSource($this->form, $this->source);
    }

    /**
     * Sets the width and height of the final cropped image.
     *
     * @param int $width The width of the final cropped image.
     * @param int $height The height of the final cropped image.
     */
    public function setSize($width, $height) {
        if ($width <= 0 || $height <= 0) {
            return;
        }
        $this->setWidth($width);
        $this->setHeight($height);
        $this->updateHiddenSize($this->form, $this->width, $this->height);
    }

    /**
     * Calculates the cropped image's dimension and location values.
     *
     * @return array An array of the cropped image's dimension and location values.
     */
    public function getCropValues() {
        $x = $this->getCropXValue();
        $y = $this->getCropYValue();
        $w = $this->getCropWidth();
        $h = $this->getCropHeight();
        return ['SourceX'      => $x,
                     'SourceY'      => $y,
                     'SourceWidth'  => $w,
                     'SourceHeight' => $h];
    }

    /**
     * @return int The height of the cropped image.
     */
    public function getCropHeight() {
        return $this->form->getValue('h');
    }

    /**
     * @return int The width of the cropped image.
     */
    public function getCropWidth() {
        return $this->form->getValue('w');
    }

    /**
     * @return int The x value of the cropped image's location.
     */
    public function getCropXValue() {
        return $this->form->getValue('x');
    }

    /**
     * @return int The y value of the cropped image's location.
     */
    public function getCropYValue() {
        return $this->form->getValue('y');
    }

    /**
     * Adds the necessary fields to the form for jcrop.
     *
     * @param Form $form The form the crop module is inserted into.
     * @param int $width The width of the final cropped image.
     * @param int $height The height of the final cropped image.
     * @param string $source The path to the local copy of the image.
     */
    private function addHiddenFields($form, $width, $height, $source) {
        // JS-manipulated values
        $form->addHidden('x', '0');
        $form->addHidden('y', '0');
        $form->addHidden('w', '0');
        $form->addHidden('h', '0');

        // Constants
        $sourceSize = getimagesize($source);
        $form->addHidden('WidthSource', $sourceSize[0], true);
        $form->addHidden('HeightSource', $sourceSize[1], true);
        $form->addHidden('CropSizeWidth', $width, true);
        $form->addHidden('CropSizeHeight', $height, true);
    }

    /**
     * Updates the form's hidden crop width and crop height fields.
     *
     * @param Form $form The form the crop module is inserted into.
     * @param int $width The width of the final cropped image.
     * @param int $height The height of the final cropped image.
     */
    private function updateHiddenSize($form, $width, $height) {
        $form->addHidden('CropSizeWidth', $width, true);
        $form->addHidden('CropSizeHeight', $height, true);
    }

    /**
     * Updates the form's hidden source width and source height fields.
     *
     * @param $form The form the crop module is inserted into.
     * @param $source The path to the local copy of the image.
     */
    private function updateHiddenSource($form, $source) {
        $sourceSize = getimagesize($source);
        $form->addHidden('WidthSource', $sourceSize[0], true);
        $form->addHidden('HeightSource', $sourceSize[1], true);
    }

    /**
     * Adds the required javascript and css assets to the controller object.
     *
     * @param $sender The controller object to add assets to.
     */
    private function addAssets($sender) {
        $sender->addJsFile('jquery.jcrop.min.js', 'dashboard');
        $sender->addJsFile('cropimage.js', 'dashboard');
    }
}
