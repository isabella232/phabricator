<?php

final class HarbormasterBuildkiteBuildStepImplementation
  extends HarbormasterBuildStepImplementation {

  public function getName() {
    return pht('Build with Buildkite');
  }

  public function getGenericDescription() {
    return pht('Trigger a build in Buildkite.');
  }

  public function getBuildStepGroupKey() {
    return HarbormasterExternalBuildStepGroup::GROUPKEY;
  }

  public function getDescription() {
    return pht('Run a build in Buildkite.');
  }

  public function getEditInstructions() {
    $hook_uri = '/harbormaster/hook/circleci/';
    $hook_uri = PhabricatorEnv::getProductionURI($hook_uri);

    return pht(<<<EOTEXT
Creates a Buildkite build. See [Buildkite’s Phabricator guide](https://buildkite.com/docs/guides/phabricator) for more information.
EOTEXT
    );
  }

  public function execute(
    HarbormasterBuild $build,
    HarbormasterBuildTarget $build_target) {

    $viewer = PhabricatorUser::getOmnipotentUser();
    $settings = $this->getSettings();

    $hook_uri = PhabricatorEnv::getProductionURI('/harbormaster/hook/buildkite/');

    $post_data = array(
      // TODO:
      // Data Buildkite needs to create a build
      // The hook URI so it knows where to post back to
    );

    $future = id(new HTTPSFuture($settings['uri'], $post_data))
      ->setMethod('POST')
      ->setTimeout(60);

    $this->resolveFutures(
      $build,
      $build_target,
      array($future));

    $this->logHTTPResponse($build, $build_target, $future, $uri);

    list($status) = $future->resolve();
    if ($status->isError()) {
      throw new HarbormasterBuildFailureException();
    }
  }

  public function getFieldSpecifications() {
    return array(
      'uri' => array(
        'name' => pht('Pipeline Trigger URI'),
        'type' => 'text',
        'required' => true,
      ),
    );
  }

  public function supportsWaitForMessage() {
    return true;
  }

}
