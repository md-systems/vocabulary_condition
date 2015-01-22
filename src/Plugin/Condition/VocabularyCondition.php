<?php

/**
 * @file
 * Contains Drupal\vocabulary_condition\Plugin\Condition\Vocabulary
 */

namespace Drupal\vocabulary_condition\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Vocabulary' condition.
 *
 * @Condition(
 *   id = "vocabulary",
 *   label = @Translation("Vocabulary"),
 *   context = {
 *     "taxonomy_term" = @ContextDefinition("entity:taxonomy_term", label = @Translation("Taxonomy Term"))
 *   }
 * )
 *
 */
class VocabularyCondition extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The vocabulary storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $vocabularyStorage;

  /**
   * Creates a new NodeType instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The vocabulary storage.
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(EntityStorageInterface $entity_storage, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->vocabularyStorage = $entity_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity.manager')->getStorage('taxonomy_vocabulary'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $options = array();
    /* @var \Drupal\taxonomy\VocabularyInterface[] $vocabularies */
    $vocabularies = $this->vocabularyStorage->loadMultiple();
    foreach ($vocabularies as $vocabulary) {
      $options[$vocabulary->id()] = $vocabulary->label();
    }
    $form['bundles'] = array(
      '#title' => $this->t('Vocabularies'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $this->configuration['bundles'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'bundles' => array(),
    ) + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['bundles'] = array_filter($form_state->getValue('bundles'));
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (count($this->configuration['bundles']) > 1) {
      $bundles = $this->configuration['bundles'];
      $last = array_pop($bundles);
      $bundles = implode(', ', $bundles);
      return $this->t('The vocabulary bundle is @bundles or @last', array(
        '@bundles' => $bundles,
        '@last' => $last,
      ));
    }
    $bundle = reset($this->configuration['bundles']);
    return $this->t('The vocabulary bundle is @bundle', array('@bundle' => $bundle));
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['bundles']) && !$this->isNegated()) {
      return TRUE;
    }
    /* @var \Drupal\taxonomy\TermInterface $term */
    $term = $this->getContextValue('taxonomy_term');
    return !empty($this->configuration['bundles'][$term->getVocabularyId()]);
  }

}
