<?php

namespace App\Form\TeamTrait;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TeamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('teamName', TextType::class, ['help' => 'help.team_name']);
        $builder->add('affiliation', TextType::class, ['help' => 'help.affiliation']);

        $builder->add('contactName', TextType::class, ['help' => 'help.contact_name']);
        $builder->add('contactEmail', EmailType::class);

        $builder->add('webpage', TextType::class, ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'trait_team',
        ]);
        parent::configureOptions($resolver);
    }
}
