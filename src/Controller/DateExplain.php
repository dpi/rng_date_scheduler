<?php

/**
 * @file
 * Contains \Drupal\rng_date_scheduler\Controller\DateExplain.
 */

namespace Drupal\rng_date_scheduler\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Provides dynamic tasks.
 */
class DateExplain extends ControllerBase {

  public function eventDates(EntityInterface $rng_event) {
    $render = [];
    $render['#attached']['library'][] = 'rng_date_scheduler/rng_date_scheduler.user';

    $row = [];
    $row_dates = [];

    $previous_after = NULL;
    $dates = rng_date_scheduler_get($rng_event);
    foreach ($dates as $date) {
      /** @var \Drupal\datetime\Plugin\Field\FieldType\DateTimeFieldItemList $field_item_list */
      $field_item_list = $rng_event->{$date->getFieldName()};

      $before = $date->canAccessBefore();
      $after = $date->canAccessAfter();

      $row[] = $this->permittedCell([$previous_after, $before]);

      $row_dates[]['#plain_text'] = \Drupal::service('date.formatter')
        ->format($date->getDate()->format('U'), 'long');

      $row[]['#plain_text'] = $field_item_list->getFieldDefinition()
        ->getLabel();

      $previous_after = $after;
    }

    $row[] = $this->permittedCell([$previous_after]);

    $render['table'] = [
      '#type' => 'table',
      '#attributes' => ['class' => 'rng-date-scheduler-explain']
    ];

    // Add the date indicator row.
    $now = DrupalDateTime::createFromTimestamp(\Drupal::request()->server->get('REQUEST_TIME'));
    $row_indicator = [];
    $d = 0;
    $current = FALSE;
    for ($i = 0; $i < count($row); $i+=2) {
      // !isset detects after last day, as the index does not exist.
      if (!$current && (!isset($dates[$d]) || $now < $dates[$d]->getDate())) {
        $row_indicator[] = [
          '#markup' => $this->t('Now'),
          '#wrapper_attributes' => ['class' => ['active-time']]
        ];
        $current = TRUE;
      }
      else {
        $row_indicator[]['#wrapper_attributes'] = ['class' => ['inactive-time']];
        $row_indicator[]['#wrapper_attributes'] = ['class' => ['inactive-time']];
      }
      $d++;
    }

    $render['table'][] = $row;
    $render['table']['dates'] = $row_dates;
    $render['table']['indicator'] = $row_indicator;
    $render['table']['indicator']['#attributes'] = ['class' => ['current-indicator']];

    return $render;
  }

  function permittedCell(array $access) {
    $forbidden = in_array(FALSE, $access, TRUE);
    $class = $forbidden ? 'forbidden' : 'neutral';
    $cell = [
      '#wrapper_attributes' => ['class' => [$class], 'rowspan' => 2],
      '#markup' => $forbidden ? $this->t('New registrations forbidden') : $this->t('Neutral'),
    ];
    return $cell;
  }

}
