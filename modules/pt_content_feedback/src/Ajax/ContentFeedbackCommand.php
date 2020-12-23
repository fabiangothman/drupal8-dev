<?php

namespace Drupal\pt_content_feedback\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class ContentFeedbackCommand.
 */
class ContentFeedbackCommand implements CommandInterface {

  private $result;

  /**
   * Constructor.
   *
   * @param int $result
   *   Result of storing feedback in database.
   */
  public function __construct($result) {
    $this->result = $result;
  }

  /**
   * Render custom ajax command.
   *
   * @return ajax
   *   Command function.
   */
  public function render() {
    return [
      'command' => 'scoreContent',
      'result' => $this->result,
    ];
  }

}
