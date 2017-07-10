<?php

namespace Drupal\webform_serialization\Normalizer;

use Drupal\jsonapi\Normalizer\ConfigEntityNormalizer as JsonapiConfigEntityNormalizer;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformSubmissionForm;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Converts a webform submission into JSON API array structure.
 */
class WebformSubmissionNormalizer extends JsonapiConfigEntityNormalizer {
  protected $supportedInterfaceOrClass = WebformSubmission::class;

  /**
   * {@inheritdoc}
   *
   * Include webform submission data in the serialized submission.
   */
  protected function getFields($entity, $bundle, ResourceType $resource_type) {
    $fields = parent::getFields($entity, $bundle, $resource_type);
    $fields['data'] = $entity->getData();
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    $resource_type = $context['resource_type'];
    $bundle = $resource_type->getBundle();

    // Ensure webform is open.
    $webform = Webform::load($bundle);
    if (!WebformSubmissionForm::isOpen($webform)) {
      throw new \Exception("The webform is not open currently open for submission");
    }

    // Validate submission data.
    $values = [
      'webform_id' => $bundle,
      'data' => $data['data'],
    ];
    $errors = WebformSubmissionForm::validateValues($values);
    if (!empty($errors)) {
      throw new BadRequestHttpException("Invalid submission values: ");
    }

    $denormalized = parent::denormalize($data, $class, $format, $context);

    // @todo: convert file metadata to whatever webform expects.
    return $denormalized;
  }

}
