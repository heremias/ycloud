<?php

namespace Drupal\geysir\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Functionality to edit a paragraph.
 */
class GeysirParagraphForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $route_match = $this->getRouteMatch();
    $parent_entity_type = $route_match->getParameter('parent_entity_type');
    $parent_entity_revision = $route_match->getParameter('parent_entity_revision');
    $field_name = $route_match->getParameter('field');
    $delta = $route_match->getParameter('delta');

    // Get the parent revision if available, otherwise the parent.
    $parent_entity_revision = $this->getParentRevisionOrParent($parent_entity_type, $parent_entity_revision);

    $field = $parent_entity_revision->get($field_name);
    $field_definition = $field->getFieldDefinition();
    $field_label = $field_definition->getLabel();

    $form['#title'] = $this->t('Edit @delta of @field of %label', [
      '@delta' => $delta,
      '@field' => $field_label,
      '%label' => $parent_entity_revision->label(),
    ]);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $route_match = $this->getRouteMatch();
    $parent_entity_type = $route_match->getParameter('parent_entity_type');
    $parent_entity_revision = $route_match->getParameter('parent_entity_revision');
    $field = $route_match->getParameter('field');
    $delta = $route_match->getParameter('delta');

    $this->entity->setNewRevision(TRUE);
    $this->entity->save();

    // Get the parent revision if available, otherwise the parent.
    $parent_entity_revision = $this->getParentRevisionOrParent($parent_entity_type, $parent_entity_revision);

    $parent_entity_revision->get($field)->get($delta)->setValue([
      'target_id' => $this->entity->id(),
      'target_revision_id' => $this->entity->getRevisionId(),
    ]);

    $save_status = $parent_entity_revision->save();

    // Use the parent revision id if available, otherwise the parent id.
    $parent_revision_id = ($parent_entity_revision->getRevisionId()) ? $parent_entity_revision->getRevisionId() : $parent_entity_revision->id();
    $form_state->setTemporary(['parent_entity_revision' => $parent_revision_id]);

    return $save_status;
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
