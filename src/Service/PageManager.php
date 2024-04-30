<?php

namespace OHMedia\PageBundle\Service;

use OHMedia\PageBundle\Entity\Page;
use OHMedia\PageBundle\Entity\Page301;
use OHMedia\PageBundle\Form\Type\AbstractPageTemplateType;
use OHMedia\PageBundle\Repository\Page301Repository;
use OHMedia\PageBundle\Repository\PageRepository;

class PageManager
{
    private array $pageTemplateTypes = [];

    public function __construct(
        private PageRawQuery $pageRawQuery,
        private PageRepository $pageRepository,
        private Page301Repository $page301Repository
    ) {
    }

    public function addPageTemplateType(AbstractPageTemplateType $pageTemplateType): self
    {
        $this->pageTemplateTypes[] = $pageTemplateType;

        return $this;
    }

    public function getPageTemplateTypes(): array
    {
        return $this->pageTemplateTypes;
    }

    public function updateHierarchy()
    {
        $homepage = $this->pageRepository->findOneBy([
            'homepage' => true,
        ]);

        if ($homepage) {
            $this->updateHomepageHierarchy($homepage);
        }

        $orderGlobal = 0;

        $path = '';

        $pages = $this->pageRepository->getTopLevel();

        if ($homepage) {
            array_unshift($pages, $homepage);
        }

        $this->updateHierarchyRecursive($orderGlobal, 0, $path, ...$pages);
    }

    private function updateHomepageHierarchy(Page $homepage)
    {
        $pages = $homepage->getPages();

        $parent = $homepage->getParent();

        if (!$pages && !$parent) {
            // nothing to worry about
            return;
        }

        $parentId = $parent ? $parent->getId() : null;

        // the page hierarchy should already be non-existent
        // but this is just a fallback

        foreach ($pages as $page) {
            $this->pageRawQuery->update($page->getId(), [
                'parent_id' => $parentId,
            ]);
        }

        if ($parentId) {
            $this->pageRawQuery->update($homepage->getId(), [
                'parent_id' => null,
            ]);
        }
    }

    private function updateHierarchyRecursive(
        int &$orderGlobal,
        int $nestingLevel,
        string $path,
        Page ...$pages
    ) {
        foreach ($pages as $i => $page) {
            $oldPath = $page->getPath();

            $newPath = $path.$page->getSlug();

            if ($oldPath && ($oldPath !== $newPath)) {
                $page301 = new Page301();
                $page301->setPath($oldPath);

                $page->addPage301($page301);

                $this->page301Repository->save($page301, true);
            }

            $orderLocal = $i;

            // NOTE: using raw queries to avoid entity listeners
            $this->pageRawQuery->update($page->getId(), [
                'path' => $newPath,
                'order_local' => $orderLocal,
                'order_global' => $orderGlobal++,
                'nesting_level' => $nestingLevel,
            ]);

            $children = $this->pageRepository->createQueryBuilder('p')
                ->where('p.parent = :parent')
                ->setParameter('parent', $page->getId())
                ->orderBy('p.order_local')
                ->getQuery()
                ->getResult();

            $this->updateHierarchyRecursive(
                $orderGlobal,
                $nestingLevel + 1,
                $newPath.'/',
                ...$children
            );
        }
    }
}
