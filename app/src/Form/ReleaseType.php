<?php

namespace App\Form;

use App\Entity\Shelf;
use App\Entity\Artist;
use App\Entity\Format;
use App\Entity\Release;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class ReleaseType extends AbstractType
{
    public function __construct(private SluggerInterface $slugger) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'empty_data' => ''
            ])
            ->add('release_date', NumberType::class, [
                'empty_data' => null,
                'required' => false,
            ])
            ->add('cover', TextType::class, [
                'empty_data' => '',
                'required' => false,
            ])
            ->add('barcode', TextType::class, [
                'empty_data' => null,
                'required' => false,
                'constraints' => [
                    new Regex([
                        'pattern' => '/^\d*$/',
                        'message' => 'Barcode can only contain numbers'
                    ])
                ]
            ])
            ->add('slug', TextType::class, [
                'empty_data' => '',
                'required' => false
            ])
            ->add('artists', EntityType::class, [
                'class' => Artist::class,
                'choice_label' => 'name',
                'multiple' => true,
            ])
            ->add('shelf', EntityType::class, [
                'class' => Shelf::class,
                'choice_label' => 'location',
                'required' => false,
                'placeholder' => 'Select a shelf',
            ])
            ->add('format', EntityType::class, [
                'class' => Format::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Select a format',
            ])
            ->addEventListener(
                FormEvents::PRE_SUBMIT,
                $this->autoSlug(...)
            )
            ->addEventListener(
                FormEvents::POST_SUBMIT,
                $this->setTimestamps(...)
            )
            ->add('save', SubmitType::class, [
                'label' => 'Save'
            ])
        ;
    }

    public function autoSlug(PreSubmitEvent $event): void
    {
        $data = $event->getData();

        if (empty($data['slug'])) {
            $data['slug'] = $this->slugger->slug($data['title']);
            $event->setData($data);
        }
    }

    public function setTimestamps(PostSubmitEvent $event): void
    {
        $data = $event->getData();
        $data->setUpdatedAt(new \DateTimeImmutable());

        if (!$data->getId()) {
            $data->setCreatedAt(new \DateTimeImmutable());
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Release::class,
        ]);
    }
}
