<?hh // strict

namespace HHVM\UserDocumentation\Tests;

use HHVM\UserDocumentation\GuidesNavData;
use HHVM\UserDocumentation\NavDataNode;

class GuidePagesTest extends \PHPUnit_Framework_TestCase {
  public static function allGuidePages(): array<(string, string)> {
    $to_visit = array_values(GuidesNavData::getNavData());
    $out = [];

    while ($node = array_pop($to_visit)) {
      foreach ($node['children'] as $child) {
        $to_visit[] = $child;
      }
      $out[] = tuple($node['name'], $node['urlPath']);
    }

    return $out;
  }

  /**
   * @dataProvider allGuidePages
   * @large
   */
  public function testGuidePage(string $name, string $path): void {
    $guard = new XHPValidationGuard();
    $this->testGuidePageQuick($name, $path);
  }

  public function shortListOfGuidePages(): array<(string, string)> {
    return [
      // Root of a guide
      tuple('Overview: Typing', '/hack/overview/'),
      // First page of a guide
      tuple('Overview: Typing', '/hack/overview/typing'),
      // Last page of a guide
      tuple('Async: Exceptions', '/hack/async/exceptions'),
      // Spaces
      tuple(
        'Other Features: Constructor Parameter Promotion',
        '/hack/other-features/constructor-parameter-promotion',
      ),
    ];
  }

  /**
   * @group remote
   * @dataProvider shortListOfGuidePages
   * @small
   */
  public function testGuidePageQuick(string $name, string $path): void {
    $response = \HH\Asio\join(PageLoader::getPage($path));

    // /hack/foo/ => /hack/foo/introduction
    if ($response->getStatusCode() === 301) {
      $response = \HH\Asio\join(
        PageLoader::getPage($response->getHeaderLine('Location'))
      );
    }

    $this->assertSame(200, $response->getStatusCode());
    $this->assertContains($name, (string) $response->getBody());
  }

  /**
   * @group remote
   * @small
   */
  public function testExamplesRender(): void {
    $response = \HH\Asio\join(PageLoader::getPage('/hack/async/introduction'));
    $this->assertSame(200, $response->getStatusCode());

    $body = (string) $response->getBody();
    $this->assertContains('highlight highlight-php', $body);
    // Namespace declaration
    $this->assertContains('Hack\UserDocumentation\Async\Intro\Examples', $body);
  }

  /**
   * @group remote
   * @small
   */
  public function testGeneratedGuidesRender(): void {
    $response = \HH\Asio\join(
      PageLoader::getPage('/hhvm/configuration/INI-settings')
    );
    $this->assertSame(200, $response->getStatusCode());

    $body = (string) $response->getBody();
    $this->assertContains('allow_url_fopen</a></td>', $body);
  }
}
