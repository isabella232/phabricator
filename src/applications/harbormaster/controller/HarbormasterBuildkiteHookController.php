<?php

final class HarbormasterBuildkiteHookController
  extends HarbormasterController {

  public function shouldRequireLogin() {
    return false;
  }

  /**
   * @phutil-external-symbol class PhabricatorStartup
   */
  public function handleRequest(AphrontRequest $request) {
    $raw_body = PhabricatorStartup::getRawInput();
    $body = phutil_json_decode($raw_body);

    // TODO

    $response = new AphrontWebpageResponse();
    $response->setContent(pht("Request OK\n"));
    return $response;
  }

  private function updateTarget(
    HarbormasterBuildTarget $target,
    array $payload) {

    $step = $target->getBuildStep();

    // TODO
  }

}
