<div class="Hero Hero-Bans">
    <div class="Message">
        <?php
        echo t($this->data('Summary'));
        ?>
        <ul>
            <?php foreach ($this->data('Reasons', array()) as $Reason) { ?>
                <li><?php echo $Reason; ?></li>
            <?php } ?>
        </ul>
    </div>
</div>
