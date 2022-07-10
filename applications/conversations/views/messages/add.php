<?php if (!defined('APPLICATION')) exit(); ?>
<div id="ConversationForm" class="FormTitleWrapper ConversationForm">
    <?php
    echo wrap($this->data('Title'), 'h1', ['class' => 'H']);
    $this->fireEvent('BeforeMessageAdd');

    echo '<div class="FormWrapper">';
    echo $this->Form->open();
    echo $this->Form->errors();

    if ($this->data('MaxRecipients')) {
        echo '<div class="Info">';
        echo plural($this->data('MaxRecipients'), "You are limited to %s recipient.", "You are limited to %s recipients.");
        echo '</div>';
    }
    $userData = $this->Data('userData');
    echo '<div class="P">';
    echo $this->Form->label('Recipients', 'To');
    echo wrap($this->Form->textBox('To', ['MultiLine' => true, 'class' => 'MultiComplete', 'data-users' => json_encode($userData)]), 'div', ['class' => 'TextBoxWrapper']);
    echo '</div>';

    if (c('Conversations.Subjects.Visible')) {
        echo '<div class="P">';
        echo $this->Form->label('Subject', 'Subject');
        echo wrap(
            $this->Form->textBox('Subject', ['class' => 'InputBox BigInput']),
            'div',
            ['class' => 'TextBoxWrapper']);
        echo '</div>';
    }

    echo '<div class="P">';
    echo $this->Form->bodyBox('Body', ['Table' => 'ConversationMessage', 'FileUpload' => true]);
    //      echo wrap($this->Form->textBox('Body', array('MultiLine' => TRUE)), 'div', array('class' => 'TextBoxWrapper'));
    echo '</div>';

    echo '<div class="Buttons">';
    echo $this->Form->button('Start Conversation', ['class' => 'Button Primary DiscussionButton']);
    echo anchor(t('Cancel'), '/messages/inbox', 'Button Cancel');
    echo '</div>';

    echo $this->Form->close();
    echo '</div>';
    ?>
</div>
