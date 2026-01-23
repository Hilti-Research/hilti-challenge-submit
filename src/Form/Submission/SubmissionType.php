<?php

namespace App\Form\Submission;

use App\Entity\Submission;
use App\Enum\ChallengeType;
use App\Enum\EvaluationType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubmissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('iteration', NumberType::class, ['disabled' => true]);
        $builder->add('description', TextType::class, ['help' => 'help.description']);

        // $builder->add('challengeType', EnumType::class, ['class' => ChallengeType::class, 'required' => true]);
        $builder->add('evaluationType', EnumType::class, ['class' => EvaluationType::class, 'expanded' => true, 'label_attr' => ['class' => 'radio-inline']]);

        $builder->add('reportFile', FileType::class, ['required' => false, 'help' => 'help.report_file', 'attr' => ['accept' => 'application/pdf']]);
        $builder->add('solutionFile', FileType::class, ['required' => true, 'help' => 'help.solution_file', 'attr' => ['accept' => 'application/zip']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Submission::class,
            'translation_domain' => 'entity_submission',
        ]);
    }
}
