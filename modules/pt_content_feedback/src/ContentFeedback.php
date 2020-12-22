<?php

namespace Drupal\pt_content_feedback;

/**
 * Content Feedback Entity.
 */
class ContentFeedback {
  public const TABLE_NAME = 'content_feedback';
  public const SCORES = [
    'not_useful',
    'dont_care',
    'useful',
    'really_useful',
  ];

}
