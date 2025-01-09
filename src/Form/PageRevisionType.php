<?php

namespace OHMedia\PageBundle\Form;

use OHMedia\PageBundle\Entity\PageRevision;
use OHMedia\PageBundle\Form\Type\AbstractDynamicPageTemplateType;
use OHMedia\PageBundle\Form\Type\AbstractPageTemplateType;
use OHMedia\PageBundle\Service\PageManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageRevisionType extends AbstractType
{
    public function __construct(
        private PageManager $pageManager,
        private Security $security,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $page = $options['data'];

        $pageTemplateTypes = $this->pageManager->getPageTemplateTypes();

        // pre-order things for the selection
        usort($pageTemplateTypes, function (AbstractPageTemplateType $a, AbstractPageTemplateType $b) {
            $aLabel = call_user_func([$a, 'getTemplateName']);
            $bLabel = call_user_func([$b, 'getTemplateName']);

            return $a <=> $b;
        });

        // distinguish between dynamic and non-dynamic
        $dynamicTemplates = [];
        $templates = [];

        foreach ($pageTemplateTypes as $pageTemplateType) {
            $isDynamic = $pageTemplateType instanceof AbstractDynamicPageTemplateType;

            $label = call_user_func([$pageTemplateType, 'getTemplateName']);
            $value = $pageTemplateType::class;

            if ($isDynamic) {
                $dynamicTemplates[$label] = $value;
            } else {
                $templates[$label] = $value;
            }
        }

        $user = $this->security->getUser();

        if ($user->isTypeDeveloper()) {
            // dynamic templates are only available to a developer user
            // append them to the selection
            $templates = array_merge($templates, $dynamicTemplates);
        }

        $builder->add('template', ChoiceType::class, [
            'choices' => $templates,
            'expanded' => true,
            'row_attr' => [
                'class' => 'fieldset-nostyle mb-3',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PageRevision::class,
        ]);
    }
}
