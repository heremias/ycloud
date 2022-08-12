<?php

namespace Drupal\geysir\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class GeysirControllerBase extends ControllerBase {

  /**
   * The entity field manager.
   *
   * @var EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityFieldManager $entityFieldManager,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var EntityFieldManager $entityFieldManager */
    $entityFieldManager = $container->get('entity_field.manager');
    /** @var EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager = $container->get('entity_type.manager');
    return new static(
      $entityFieldManager,
      $entityTypeManager
    );
  }

  /**
   * Returns the paragraph title set for the current paragraph field.
   *
   * @param $parent_entity_type
   *   The entity type of the parent entity of this paragraphs field.
   * @param $parent_entity_bundle
   *   The bundle of the parent entity of this paragraphs field.
   * @param $field
   *   The machine name of the paragraphs field.
   *
   * @return string
   *   The paragraph title set for the current paragraph field.
   */
  protected function getParagraphTitle($parent_entity_type, $parent_entity_bundle, $field) {
    $form_mode = 'default';

    $parent_field_settings = $this->entityTypeManager
      ->getStorage('entity_form_display')
      ->load($parent_entity_type . '.' . $parent_entity_bundle . '.' . $form_mode)
      ->getComponent($field);

    $paragraph_title = isset($parent_field_settings['settings']['title']) ?
      $parent_field_settings['settings']['title'] :
      $this->t('Paragraph');

    return strtolower($paragraph_title);
  }

  /**
   * @param $parent_entity_type
   * @param $parent_entity_revision
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getParentRevisionOrParent($parent_entity_type, $parent_entity_revision) {
    $entity_storage = $this->entityTypeManager->getStorage($parent_entity_type);
    if ($this->entityTypeManager->getDefinition($parent_entity_type)->isRevisionable()) {
      return $entity_storage->loadRevision($parent_entity_revision);
    }
    else {
      return $entity_storage->load($parent_entity_revision);
    }
  }

}
