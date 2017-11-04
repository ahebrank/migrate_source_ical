##Migrate Source iCal

This module provides a source plugin to migrate iCal data.

Requirements:
-

Usage:

# Migration configuration for products.
id: events
label: Events
migration_group: dsty
migration_dependencies: {}

source:
  plugin: ical
  # track_changes: true
  high_water_property:
    name: lastmodified
  path: "https://email.dsty.ac.jp/home/webseite-jahrestermine/calendar.ics"
  identifier: upc
  identifierDepth: 1
  fields:
    - uid
    - summary
    - description
    - lastmodified
    - dtstart
    - dtend
    - location
  keys:
    - uid

destination:
  plugin: entity:node

process:
  type:
    plugin: default_value
    default_value: events
  title: summary
  body: description
  field_location: location
  field_event_date/value:
    plugin: w3c_date
    type: 'date-start'
    to_format: 'Y-m-d\TH:i:s'
    timezone: 'UTC'
    source: dtstart
  field_event_date/end_value:
    plugin: w3c_date
    to_format: 'Y-m-d\TH:i:s'
    type: 'date-end'
    timezone: 'UTC'
    source: dtend

  sticky:
    plugin: default_value
    default_value: 0
  status: 'published'
  # path:
  #   plugin: machine_name
  #   source: SUMMARY
  uid:
    plugin: default_value
    default_value: 1
