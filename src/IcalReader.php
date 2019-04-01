<?php

/**
 * @file
 *
 * Reads ical format into a nested array. For example:
 *
Array
(
    [DTSTART] => DateTime Object
        (
            [date] => 2015-10-13 10:30:00.000000
            [timezone_type] => 3
            [timezone] => Asia/Calcutta
        )

    [DTEND] => DateTime Object
        (
            [date] => 2015-10-13 11:00:00.000000
            [timezone_type] => 3
            [timezone] => Asia/Calcutta
        )

    [RRULE] => Array
        (
            [FREQ] => WEEKLY
            [UNTIL] => DateTime Object
                (
                    [date] => 2015-11-13 05:00:00.000000
                    [timezone_type] => 2
                    [timezone] => Z
                )

            [BYDAY] => MO,TU,WE,TH,FR
        )

    [DTSTAMP] => DateTime Object
        (
            [date] => 2017-09-22 12:02:14.000000
            [timezone_type] => 2
            [timezone] => Z
        )

    [ORGANIZER] => mailto:nvhsa43nhis4uqjiec7u9ceqa0@group.calendar.google.com
    [UID] => 59m3jpl5vasf9q7ao22m3frc9o@google.com
    [ATTENDEE] => mailto:gdgautamd5@gmail.com
    [CREATED] => DateTime Object
        (
            [date] => 2015-10-08 02:26:35.000000
            [timezone_type] => 2
            [timezone] => Z
        )

    [DESCRIPTION] =>
    [LAST-MODIFIED] => DateTime Object
        (
            [date] => 2015-11-20 07:51:34.000000
            [timezone_type] => 2
            [timezone] => Z
        )

    [LOCATION] =>
    [SEQUENCE] => 1
    [STATUS] => CONFIRMED
    [SUMMARY] => Scrum Meeting
    [TRANSP] => OPAQUE
    [0] =>
    [RECURRING] => 1
)
 */

namespace Drupal\migrate_source_ical;

use Drupal\migrate\MigrateException;
use ICal\ICal;

/**
 * Object to retrieve and iterate over JSON data.
 */
class IcalReader {

  /**
   * Source configuration.
   *
   * @var array
   */
  protected $configuration;

  /**
   * Set the configuration created by the source.
   *
   * @param array $configuration
   *   The source configuration.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function __construct(array $configuration) {
    // Store it.
    $this->configuration = $configuration;
  }

  /**
   * Fetch all fields.
   */
  public function getSourceFields($url) {

    $iterator = $this->getSourceData($url);

    // Recurse through the result array. When there is an array of items at the
    // expected depth that has the expected identifier as one of the keys, pull
    // that array out as a distinct item.
    // $identifier = $this->getIdentifier();
    // $identifierDepth = $this->getIdentifierDepth();
    $items = [];
    while ($iterator->valid()) {
      $iterator->next();
      $item = $iterator->current();
      if (is_array($item)) {
        $items[] = $item;
      }
    }

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFieldsIterator($url) {
    return $this->getSourceData($url);
  }

  /**
   * Get the source data for reading.
   *
   * @param string $url
   *   The URL to read the source data from.
   *
   * @return \ArrayIterator
   *   Event iterator.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function getSourceData($url) {
    $opts = [];
    if (isset($this->configuration['timezone'])) {
      $opts['defaultTimeZone'] = $this->configuration['timezone'];
    }
    if (isset($this->configuration['http_user_agent'])) {
      $opts['httpUserAgent'] = $this->configuration['http_user_agent'];
    }
    // if (isset($this->configuration['exclude_before_now']) && $this->configuration['exclude_before_now']) {
    //   $opts['filterDaysBefore'] = 1;
    // }

    try {
      $response = [];
      $ical = new ICal($url, $opts);
      foreach ($ical->events() as $ical_event) {
        $event = (array) $ical_event;
        $event['dtstart'] = $this->getDatetime($ical_event->dtstart_array);
        $event['dtend'] = $this->getDatetime($ical_event->dtend_array);
        $response[] = $event;
      }
      // Each object returns and event array.
      return new \ArrayIterator($response);
    }
    catch (Exception $e) {
      throw new MigrateException($e->getMessage());
    }
  }

  /**
   * Take an Ical date array and return Y-m-d\TH:i:s.
   *
   * @param array $date_array
   *   These look like:
   *     // array (size=4)
   *     //   0 =>
   *     //     array (size=1)
   *     //       'TZID' => string 'America/Detroit' (length=15)
   *     //   1 => string '20160409T090000' (length=15)
   *     //   2 => int 1460192400
   *     //   3 => string 'TZID=America/Detroit:20160409T090000' (length=36)
   *
   * @return string
   *   Formatted datetime for field value.
   */
  protected function getDatetime($date_array) {
    if (!is_array($date_array) || !isset($date_array[2])) {
      return NULL;
    }
    $date = \DateTime::createFromFormat('U', $date_array[2]);
    return $date->format('Y-m-d\TH:i:s');
  }

}
