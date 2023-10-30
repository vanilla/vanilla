<?php

namespace VanillaTests\Library\Vanilla\Formatting;

trait UserMentionTestTraits
{
    public $USERNAME_NO_SPACE = "UserNoSpace";
    public $USERNAME_WITH_SPACE = "User With Space";
    public $PROFILE_URL_NO_SPACE = "/profile/UserNoSpace";
    public $PROFILE_URL_WITH_SPACE = "/profile/User%20With%20Space";

    /**
     * Return valid AtMention patterns that are common for all non-rich formats.
     *
     * @return array
     */
    public function provideAtMention(): array
    {
        return [
            "validAtMentionEndWithWhiteSpace" => [
                "@$this->USERNAME_NO_SPACE @\"user 1\" Some fluff text to make sure inline UserNoSpace is not removed.",
                ["user 1", "UserNoSpace"],
            ],
            "validAtMentionEndWithDot" => [
                "@$this->USERNAME_NO_SPACE. Some fluff text to make sure inline UserNoSpace is not removed.",
            ],
            "validAtMentionEndWithComma" => [
                "@$this->USERNAME_NO_SPACE, Some fluff text to make sure inline UserNoSpace is not removed.",
            ],
            "validAtMentionEndWithSemiColon" => [
                "@$this->USERNAME_NO_SPACE; Some fluff text to make sure inline UserNoSpace is not removed.",
            ],
            "validAtMentionEndWithInterrogationMark" => [
                "@$this->USERNAME_NO_SPACE? Some fluff text to make sure inline UserNoSpace is not removed.",
            ],
            "@validAtMentionEndWithExclamationMark" => [
                "@$this->USERNAME_NO_SPACE! Some fluff text to make sure inline UserNoSpace is not removed.",
            ],
            "validAtMentionEndWithSingleQuote" => [
                "@$this->USERNAME_NO_SPACE' Some fluff text to make sure inline UserNoSpace is not removed.",
            ],
            "validAtMentionEOF" => ["@$this->USERNAME_NO_SPACE"],
            "validAtMentionSkipLine" => [
                "@$this->USERNAME_NO_SPACE
                ",
            ],
            "validAtMentionColon" => ["@$this->USERNAME_NO_SPACE:"],
            "validAtMentionWithSpace" => [
                "@\"$this->USERNAME_WITH_SPACE\" Some fluff text to make sure inline User With Space is not removed.",
                [$this->USERNAME_WITH_SPACE],
            ],
            "validAtMentionWithMultiple" => ["@user1 @user2 @\"user 3\"", ["user1", "user2", "user 3"]],
            "noAtMention" => ["no@email.com \"this is a random quote for\"", []],
        ];
    }

    /**
     * Provide valid User Urls data.
     *
     * @return array
     */
    public function provideProfileUrl(): array
    {
        $baseUrl = static::getBaseUrl();
        return [
            "validUrlNoSpace" => [$baseUrl . $this->PROFILE_URL_NO_SPACE],
            "validUrlWithSpace" => [$baseUrl . $this->PROFILE_URL_WITH_SPACE, [$this->USERNAME_WITH_SPACE]],
            "invalidUrlOtherCommunity" => ["https://dev.vanilla.com/profile/UserToAnonymize", []],
        ];
    }
}
