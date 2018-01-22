<?php

namespace Drupal\rules\Context;

use Drupal\Core\Plugin\Context\ContextDefinition as ContextDefinitionCore;
use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\TypedData\Plugin\DataType\Email;
use Drupal\Core\TypedData\Type\DateTimeInterface;
use Drupal\Core\TypedData\Type\DurationInterface;
use Drupal\Core\TypedData\Type\FloatInterface;
use Drupal\Core\TypedData\Type\IntegerInterface;
use Drupal\Core\TypedData\Type\StringInterface;
use Drupal\Core\TypedData\Type\UriInterface;

/**
 * Extends the core context definition class with useful methods.
 */
class ContextDefinition extends ContextDefinitionCore implements ContextDefinitionInterface {

  /**
   * The mapping of config export keys to internal properties.
   *
   * @var array
   */
  protected static $nameMap = [
    'type' => 'dataType',
    'label' => 'label',
    'description' => 'description',
    'multiple' => 'isMultiple',
    'required' => 'isRequired',
    'default_value' => 'defaultValue',
    'constraints' => 'constraints',
    'allow_null' => 'allowNull',
    'assignment_restriction' => 'assignmentRestriction',
  ];

  /**
   * Whether the context value is allowed to be NULL or not.
   *
   * @var bool
   */
  protected $allowNull = FALSE;

  /**
   * The assignment restriction of this context.
   *
   * @var string|null
   *
   * @see \Drupal\rules\Context\ContextDefinitionInterface::getAssignmentRestriction()
   */
  protected $assignmentRestriction = NULL;

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $values = [];
    $defaults = get_class_vars(__CLASS__);
    foreach (static::$nameMap as $key => $property_name) {
      // Only export values for non-default properties.
      if ($this->$property_name !== $defaults[$property_name]) {
        $values[$key] = $this->$property_name;
      }
    }
    return $values;
  }

  /**
   * Creates a definition object from an exported array of values.
   *
   * @param array $values
   *   The array of values, as returned by toArray().
   *
   * @return static
   *   The created definition.
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   *   If the required classes are not implemented.
   */
  public static function createFromArray(array $values) {
    if (isset($values['class']) && !in_array(ContextDefinitionInterface::class, class_implements($values['class']))) {
      throw new ContextException('ContextDefinition class must implement ' . ContextDefinitionInterface::class . '.');
    }
    // Default to Rules context definition class.
    $values['class'] = isset($values['class']) ? $values['class'] : ContextDefinition::class;
    if (!isset($values['value'])) {
      $values['value'] = 'any';
    }

    $definition = $values['class']::create($values['value']);
    foreach (array_intersect_key(static::$nameMap, $values) as $key => $name) {
      $definition->$name = $values[$key];
    }
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowedNull() {
    return $this->allowNull;
  }

  /**
   * {@inheritdoc}
   */
  public function setAllowNull($null_allowed) {
    $this->allowNull = $null_allowed;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAssignmentRestriction() {
    return $this->assignmentRestriction;
  }

  /**
   * {@inheritdoc}
   */
  public function setAssignmentRestriction($restriction) {
    $this->assignmentRestriction = $restriction;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetId() {
    // TODO Should we make TextareaWidget::isApplicable() static and use here?
    $definition_class = $this->getDataDefinition()->getClass();
    $widgets = [
      'text_input' => [
        DateTimeInterface::class,
        DurationInterface::class,
        Email::class,
        FloatInterface::class,
        IntegerInterface::class,
        // TODO Should we use text_input or textarea for strings?
        StringInterface::class,
        UriInterface::class,
      ],
      'textarea' => [
        StringInterface::class,
      ]
    ];

    foreach ($widgets as $widget_id => $data_types) {
      foreach ($data_types as $data_type) {
        if (is_subclass_of($definition_class, $data_type)) {
          return $widget_id;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetSettings() {
    return [];
  }

}
