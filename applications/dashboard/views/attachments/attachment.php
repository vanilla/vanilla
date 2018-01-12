<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

if (!function_exists('WriteAttachment')) {
    /**
     * Renders attachments.  Checks for error key and if present will display error using WriteErrorAttachment.
     *
     * @param array $attachment Attachment
     * @return string
     */
    function writeAttachment($attachment) {

        $customMethod = AttachmentModel::getWriteAttachmentMethodName($attachment['Type']);
        if (function_exists($customMethod)) {
            if (val('Error', $attachment)) {
                writeErrorAttachment($attachment);
                return;
            }
            $customMethod($attachment);
        } else {
            trace($customMethod, 'Write Attachment method not found');
            trace($attachment, 'Attachment');
        }
        return;
    }

}

if (!function_exists('WriteAttachments')) {
    function writeAttachments($attachments) {
        foreach ($attachments as $attachment) {
            ?>
            <div class="item-attachments">
                <?php writeAttachment($attachment); ?>
            </div>
        <?php
        }
    }
}

if (!function_exists('WriteSkeletonAttachment')) {
    function writeSkeletonAttachment($attachment) {
        ?>
        <div class="item-attachment">
            <div class="alert">
                <div class="media item">
                    <div class="pull-left">
                        <div class="media-object">
                            <i class="icon icon-tag"></i>
                        </div>
                    </div>
                    <div class="media-body">

                        <div class="item-header">
                            <h4 class="media-heading item-heading">Heading
                                <div class="item-meta">
                                    <span>heading</span>
                                </div>
                            </h4>
                        </div>


                        <div class="item-body">

                            <dl class="dl-columns">
                                <dt>Name 1</dt>
                                <dd>Value 1</dd>
                                <dt>Name 2</dt>
                                <dd>Value 2</dd>
                                <dt>Name 3</dt>
                                <dd>Value 3</dd>
                                <dt>Name 4</dt>
                                <dd>Value 4</dd>

                            </dl>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    <?php
    }
}

if (!function_exists('WriteErrorAttachment')) {
    /**
     * Given a parsed attachment, render it in HTML
     *
     * @param array $attachment
     * @return string
     */
    function writeErrorAttachment($attachment) {
        writeGenericAttachment([
            'Type' => 'Warning',
            'Icon' => 'warning-sign',
            'Body' => $attachment['Error']
        ]);
    }
}

if (!function_exists('WriteGenericAttachment')) {
    /**
     * Given a parsed attachment, render it in HTML
     *
     * @param array $attachment
     * @return string
     */
    function writeGenericAttachment($attachment) {
        $type = val('Type', $attachment);
        $icon = val('Icon', $attachment, 'sign-blank');
        $title = val('Title', $attachment);
        $meta = val('Meta', $attachment);
        $body = val('Body', $attachment);
        $fields = val('Fields', $attachment);

        ?>
        <div class="item-attachment">
            <div class="alert<?php if ($type) echo ' alert-'.strtolower($type); ?>">
                <div class="media item">
                    <div class="pull-left">
                        <div class="media-object">
                            <i class="icon icon-<?php echo $icon; ?>"></i>
                        </div>
                    </div>
                    <div class="media-body">
                        <?php if ($title || $meta): ?>

                            <div class="item-header">
                                <?php if ($title): ?>
                                <h4 class="media-heading item-heading"><?php echo Gdn_Format::html($title); ?>
                                    <?php endif; ?>

                                    <?php if ($meta): ?>
                                        <div class="item-meta">
                                            <?php foreach ($meta as $item): ?>
                                                <span><?php echo Gdn_Format::html($item); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                            </div>

                        <?php endif; ?>

                        <?php if ($body || $fields): ?>

                            <div class="item-body">
                                <?php if ($body): ?>
                                    <?php echo Gdn_Format::html($body); ?>
                                <?php endif; ?>

                                <?php if ($fields): ?>
                                    <dl class="dl-columns">
                                        <?php foreach ($fields as $title => $field): ?>
                                            <dt><?php echo t($title); ?></dt>
                                            <dd><?php echo Gdn_Format::html($field); ?></dd>
                                        <?php endforeach; ?>
                                    </dl>
                                <?php endif; ?>
                            </div>

                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }
}
