<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

/**
 * Class for rendering twig views with the vanilla environment configured.
 */
trait TwigRenderTrait
{
    /** @var string The path to look for twig views in. */
    protected static $twigDefaultFolder = PATH_ROOT;

    /**
     * @var \Twig\Environment
     */
    private $twig;

    /**
     * Initialize the twig environment.
     */
    private function prepareTwig(): \Twig\Environment
    {
        return \Gdn::getContainer()->get(TwigRenderer::class);
    }

    /**
     * Render a given view using twig.
     *
     * @param string $path The view path.
     * @param array $data The data to render.
     *
     * @return string Rendered HTML.
     */
    public function renderTwig(string $path, array $data): string
    {
        if (!$this->twig) {
            $this->twig = $this->prepareTwig();
        }
        // Ensure that we don't duplicate our root path in the path view.
        $path = str_replace(PATH_ROOT, "", $path);
        return $this->twig->render($path, $data);
    }

    /**
     * Render a
     *
     * @param string $templateString
     * @param array $data
     * @return string
     */
    public function renderTwigFromString(string $templateString, array $data): string
    {
        if (!$this->twig) {
            $this->twig = $this->prepareTwig();
        }

        $template = $this->twig->createTemplate($templateString);
        return $template->render($data);
    }

    /**
     * Render a twig template of a list of links.
     *
     * @param array<array{name: string, url: string}|\ArrayAccess> $links
     *
     * @return string
     */
    public function renderSeoLinkList($links): string
    {
        foreach ($links as &$link) {
            // Normalize certain widget items.
            $link["url"] = $link["url"] ?? ($link["Url"] ?? $link["to"]);
            $link["name"] = $link["name"] ?? ($link["Name"] ?? ($link["label"] ?? ($link["title"] ?? null)));
            $link["excerpt"] = $link["excerpt"] ?? ($link["description"] ?? null);
        }
        $tpl = <<<TWIG
<ul class="linkList">
{% for link in links %}
{% if link.url|default(false) and link.name|default(false) %}
<li>
<a href="{{ link.url }}">{{- link.name -}}</a>
{% if link.excerpt|default(false) %}
<p>{{ link.excerpt }}</p>
{% endif %}
</li>
{% endif %}
{% endfor %}
</ul>
TWIG;

        $result = $this->renderTwigFromString($tpl, ["links" => $links]);
        return $result;
    }

    /**
     * Render a user for SEO content.
     *
     * @param array|\ArrayAccess $user
     *
     * @return string
     */
    public function renderSeoUser($user): string
    {
        $tpl = <<<TWIG
<a href="{{ user.url }}" class="seoUser">
<img height="24px" width="24px" src="{{ user.photoUrl }}" alt="Photo of {{ user.name }}" />
<span class="seoUserName">{{ user.name }}</span>
</a>
TWIG;

        $result = $this->renderTwigFromString($tpl, ["user" => $user]);
        return $result;
    }
}
