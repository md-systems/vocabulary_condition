<?php

/**
 * @file
 * Contains Drupal\vocabulary_condition\Tests\Plugin\Condition\VocabularyConditionTest
 */

namespace Drupal\vocabulary_condition\Tests\Plugin\Condition;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Language\LanguageInterface;
use Drupal\system\Tests\Entity\EntityUnitTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Tests the vocabulary condition test.
 *
 * @group vocabulary_condition
 */
class VocabularyConditionTest extends EntityUnitTestBase {

  public static $modules = array('taxonomy', 'vocabulary_condition');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('taxonomy_term');
  }

  /**
   * Tests the vocabulary conditions.
   */
  public function testConditions() {

    /* @var \Drupal\Component\Plugin\PluginManagerInterface $manager */
    $manager = $this->container->get('plugin.manager.condition');

    // Create the vocabularies required for testing.
    $vocab1 = $this->createVocabulary();
    $vocab2 = $this->createVocabulary();

    $term1 = $this->createTerm($vocab1);
    $term2 = $this->createTerm($vocab2);

    // Assert term1 is in vocabulary1.
    /* @var \Drupal\vocabulary_condition\Plugin\Condition\VocabularyCondition $condition */
    $condition = $manager->createInstance('vocabulary')
      ->setConfig('bundles', array($vocab1->id() => $vocab1->label()))
      ->setContextValue('taxonomy_term', $term1);

    $this->assertTrue($condition->execute(), String::format('The term @term is in vocabulary @vocab', array(
      '@term' => $term1->label(),
      '@vocab' => $vocab1->label(),
    )));

    // Assert the summary text is correct.
    $this->assertEqual($condition->summary(), String::format('The vocabulary bundle is @vocab', array('@vocab' => $vocab1->label())));

    // Assert a term not in a vocabulary does not match.
    $condition = $manager->createInstance('vocabulary')
      ->setConfig('bundles', array($vocab1->id() => $vocab1->label()))
      ->setContextValue('taxonomy_term', $term2);

    $this->assertFalse($condition->execute(), String::format('The term @term is NOT in vocabulary @vocab', array(
      '@term' => $term1->label(),
      '@vocab' => $vocab2->label(),
    )));

    // Assert term1 is in in either vocab1 or vocab2.
    /* @var \Drupal\vocabulary_condition\Plugin\Condition\VocabularyCondition $condition */
    $condition = $manager->createInstance('vocabulary')
      ->setConfig('bundles', array(
        $vocab1->id() => $vocab1->label(),
        $vocab2->id() => $vocab2->label(),
      ))
      ->setContextValue('taxonomy_term', $term1);

    $this->assertTrue($condition->execute(), String::format('The term @term is in vocabulary @vocab', array(
      '@term' => $term1->label(),
      '@vocab' => $vocab1->label(),
    )));

    // Assert the summary text is correct.
    $this->assertEqual($condition->summary(), String::format('The vocabulary bundle is @vocab1 or @vocab2', array(
      '@vocab1' => $vocab1->label(),
      '@vocab2' => $vocab2->label(),
    )));

  }

  /**
   * Returns a new vocabulary with random properties.
   */
  protected function createVocabulary() {
    // Create a vocabulary.
    $vocabulary = Vocabulary::create(array(
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      'vid' => Unicode::strtolower($this->randomMachineName()),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $vocabulary->save();
    return $vocabulary;
  }

  /**
   * Returns a new term with random properties in vocabulary $vid.
   */
  protected function createTerm(VocabularyInterface $vocabulary) {
    $term = Term::create(array(
      'name' => $this->randomMachineName(),
      'vid' => $vocabulary->id(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $term->save();
    return $term;
  }
}
