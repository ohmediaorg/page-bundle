<?php

namespace OHMedia\PageBundle\Twig;

use Doctrine\ORM\QueryBuilder;
use OHMedia\FileBundle\Service\ImageManager;
use OHMedia\PageBundle\Entity\AbstractPageContent;
use OHMedia\PageBundle\Entity\PageContentText;
use OHMedia\PageBundle\Repository\AbstractPageContentRepository;
use OHMedia\PageBundle\Repository\PageContentCheckboxRepository;
use OHMedia\PageBundle\Repository\PageContentImageRepository;
use OHMedia\PageBundle\Repository\PageContentRowRepository;
use OHMedia\PageBundle\Repository\PageContentTextRepository;
use OHMedia\PageBundle\Service\PageRenderer;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PageContentExtension extends AbstractExtension
{
    public function __construct(
        private ImageManager $imageManager,
        private PageContentCheckboxRepository $contentCheckboxRepo,
        private PageContentImageRepository $contentImageRepo,
        private PageContentRowRepository $contentRowRepo,
        private PageContentTextRepository $contentTextRepo,
        private PageRenderer $pageRenderer
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('content_checkbox', [$this, 'contentCheckbox']),
            new TwigFunction('content_choice_exists', [$this, 'contentChoiceExists']),
            new TwigFunction('content_choice', [$this, 'renderContentChoice']),
            new TwigFunction('content_image_exists', [$this, 'contentImageExists']),
            new TwigFunction('content_image_path', [$this, 'renderContentImagePath']),
            new TwigFunction('content_image_tag', [$this, 'renderContentImageTag'], [
                'is_safe' => ['html'],
                'needs_environment' => true,
            ]),
            new TwigFunction('content_row_exists', [$this, 'contentRowExists']),
            new TwigFunction('content_row', [$this, 'renderContentRow'], [
                'is_safe' => ['html'],
                'needs_environment' => true,
            ]),
            new TwigFunction('content_text_exists', [$this, 'contentTextExists']),
            new TwigFunction('content_text', [$this, 'renderContentText'], [
                'is_safe' => ['html'],
                'needs_environment' => true,
            ]),
            new TwigFunction('content_textarea_exists', [$this, 'contentTextareaExists']),
            new TwigFunction('content_textarea', [$this, 'renderContentTextarea'], [
                'is_safe' => ['html'],
                'needs_environment' => true,
            ]),
            new TwigFunction('content_wysiwyg_exists', [$this, 'contentWysiwygExists']),
            new TwigFunction('content_wysiwyg', [$this, 'renderContentWysiwyg'], [
                'is_safe' => ['html'],
                'needs_environment' => true,
            ]),
        ];
    }

    public function contentCheckbox(string $name): bool
    {
        $queryBuilder = $this->getContentQueryBuilder($this->contentCheckboxRepo, 'c', $name)
            ->andWhere('c.checked = 1');

        return $this->queryBuilderHasResult($queryBuilder);
    }

    public function contentImageExists(string $name): bool
    {
        $queryBuilder = $this->getContentImageQueryBuilder($name);

        return $this->queryBuilderHasResult($queryBuilder);
    }

    public function renderContentImagePath(string $name, int $width = null, int $height = null): string
    {
        $queryBuilder = $this->getContentImageQueryBuilder($name);

        $content = $this->getContent($queryBuilder);

        if (!$content) {
            return '';
        }

        $image = $content->getImage();

        if (!$image) {
            return '';
        }

        return $this->imageManager->getImagePath($image, $width, $height);
    }

    public function renderContentImageTag(Environment $twig, string $name, array $attributes = []): string
    {
        $queryBuilder = $this->getContentImageQueryBuilder($name);

        $content = $this->getContent($queryBuilder);

        return $twig->render('@OHMediaPage/content/image_tag.html.twig', [
            'name' => $name,
            'content' => $content,
            'attributes' => $attributes,
            'page_revision' => $this->pageRenderer->getCurrentPageRevision(),
        ]);
    }

    public function contentRowExists(string $name): bool
    {
        $queryBuilder = $this->getContentRowQueryBuilder($name);

        return $this->queryBuilderHasResult($queryBuilder);
    }

    public function renderContentRow(Environment $twig, string $name, array $allowedTags = null): string
    {
        $queryBuilder = $this->getContentRowQueryBuilder($name);

        $content = $this->getContent($queryBuilder);

        if (!$content || !$content->getLayout()) {
            return '';
        }

        return $twig->render('@OHMediaPage/content/row.html.twig', [
            'name' => $name,
            'content' => $content,
            'allowed_tags' => $allowedTags,
            'page_revision' => $this->pageRenderer->getCurrentPageRevision(),
        ]);
    }

    public function contentChoiceExists(string $name): bool
    {
        $queryBuilder = $this->getContentTextQueryBuilder($name, PageContentText::TYPE_CHOICE);

        return $this->queryBuilderHasResult($queryBuilder);
    }

    public function renderContentChoice(string $name): string
    {
        $queryBuilder = $this->getContentTextQueryBuilder($name, PageContentText::TYPE_CHOICE);

        $content = $this->getContent($queryBuilder);

        return $content ? $content->getText() : '';
    }

    public function contentTextExists(string $name): bool
    {
        $queryBuilder = $this->getContentTextQueryBuilder($name, PageContentText::TYPE_TEXT);

        return $this->queryBuilderHasResult($queryBuilder);
    }

    public function renderContentText(Environment $twig, string $name): string
    {
        $queryBuilder = $this->getContentTextQueryBuilder($name, PageContentText::TYPE_TEXT);

        $content = $this->getContent($queryBuilder);

        return $content ? (string) $content->getText() : '';
    }

    public function contentTextareaExists(string $name): bool
    {
        $queryBuilder = $this->getContentTextQueryBuilder($name, PageContentText::TYPE_TEXTAREA);

        return $this->queryBuilderHasResult($queryBuilder);
    }

    public function renderContentTextarea(Environment $twig, string $name): string
    {
        $queryBuilder = $this->getContentTextQueryBuilder($name, PageContentText::TYPE_TEXTAREA);

        $content = $this->getContent($queryBuilder);

        return $content
            ? nl2br(htmlspecialchars((string) $content->getText()))
            : '';
    }

    public function contentWysiwygExists(string $name): bool
    {
        $queryBuilder = $this->getContentTextQueryBuilder($name, PageContentText::TYPE_WYSIWYG);

        return $this->queryBuilderHasResult($queryBuilder);
    }

    public function renderContentWysiwyg(Environment $twig, string $name, array $allowedTags = null): string
    {
        $queryBuilder = $this->getContentTextQueryBuilder($name, PageContentText::TYPE_WYSIWYG);

        $content = $this->getContent($queryBuilder);

        return $twig->render('@OHMediaPage/content/wysiwyg.html.twig', [
            'name' => $name,
            'content' => $content,
            'allowed_tags' => $allowedTags,
            'page_revision' => $this->pageRenderer->getCurrentPageRevision(),
        ]);
    }

    private function queryBuilderHasResult(QueryBuilder $queryBuilder): bool
    {
        $aliases = $queryBuilder->getRootAliases();

        if (!isset($aliases[0])) {
            throw new \RuntimeException('No alias was set before invoking queryBuilderHasResult().');
        }

        $select = sprintf('COUNT(%s.id)', $aliases[0]);

        return (clone $queryBuilder)
            ->select($select)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    private function getContent(QueryBuilder $queryBuilder): ?AbstractPageContent
    {
        return (clone $queryBuilder)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function getContentImageQueryBuilder(string $name): QueryBuilder
    {
        return $this->getContentQueryBuilder($this->contentImageRepo, 'c', $name)
            ->join('c.image', 'i')
            ->andWhere("i.path <> ''")
            ->andWhere('i.path IS NOT NULL');
    }

    private function getContentRowQueryBuilder(string $name): QueryBuilder
    {
        return $this->getContentQueryBuilder($this->contentRowRepo, 'c', $name)
            ->andWhere('c.layout IS NOT NULL')
            ->andWhere("c.layout <> ''");
    }

    private function getContentTextQueryBuilder(string $name, string $type): QueryBuilder
    {
        return $this->getContentQueryBuilder($this->contentTextRepo, 'c', $name)
            ->andWhere('c.type = :type')
            ->setParameter('type', $type)
            ->andWhere('c.text IS NOT NULL')
            ->andWhere("c.text <> ''");
    }

    private function getContentQueryBuilder(
        AbstractPageContentRepository $repository,
        string $alias,
        string $name
    ): QueryBuilder {
        $pageRevision = $this->pageRenderer->getCurrentPageRevision();

        return $repository->baseQueryBuilder($alias, $pageRevision, $name);
    }
}
