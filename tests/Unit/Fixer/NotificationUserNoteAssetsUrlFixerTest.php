<?php

namespace Tests\Unit\Fixer;

use PHPUnit\Framework\TestCase;
use Reverb\Fixer\NotificationUserNoteAssetsUrlFixer;

class NotificationUserNoteAssetsUrlFixerTest extends TestCase
{
	/**
	 * @dataProvider cheevosDataProvider
	 * @param string $input
	 * @param string $expectedResult
	 * @return void
	 */
	public function testCheevosHasCorrectPath(string $input, string $expectedResult): void
	{
		$fixer = new NotificationUserNoteAssetsUrlFixer('fandom.com');

		$notificationInput = [
			'user_note' => $input
		];

		$fixedNotification = $fixer->fix($notificationInput);

		self::assertSame($expectedResult, $fixedNotification['user_note']);
	}

	public function cheevosDataProvider(): array
	{
		$fullNotification = '
			<div class=\'reverb-npn-ach\'>
				<div class=\'reverb-npn-ach-text\'>
					<div class=\'reverb-npn-ach-name\'>What do we have here?</div>
					<div class=\'reverb-npn-ach-description\'>Visit the Achievements Special Page.</div>
				</div>
				<div class=\'reverb-npn-ach-points\'>10<img src="/extensions-ucp/Cheevos/images/gp30.png" />
				</div>
			</div>
		';

		$expectedFullNotification = '
			<div class=\'reverb-npn-ach\'>
				<div class=\'reverb-npn-ach-text\'>
					<div class=\'reverb-npn-ach-name\'>What do we have here?</div>
					<div class=\'reverb-npn-ach-description\'>Visit the Achievements Special Page.</div>
				</div>
				<div class=\'reverb-npn-ach-points\'>10<img src="fandom.com/extensions-ucp/mw139/Cheevos/images/gp30.png" />
				</div>
			</div>
		';

		return [
			[
				'<img src="/extensions-ucp/v2/Cheevos/images/gp30.png"',
				'<img src="fandom.com/extensions-ucp/mw139/Cheevos/images/gp30.png"',
			],
			[
				'<img src="/extensions-ucp/mw139/Cheevos/images/gp30.png"',
				'<img src="fandom.com/extensions-ucp/mw139/Cheevos/images/gp30.png"',
			],
			[
				'<img src="/extensions-ucp/v2/mw139/Cheevos/images/gp30.png"',
				'<img src="fandom.com/extensions-ucp/mw139/Cheevos/images/gp30.png"',
			],
			[
				'<img src="/extensions-ucp/Cheevos/images/gp30.png"',
				'<img src="fandom.com/extensions-ucp/mw139/Cheevos/images/gp30.png"',
			],
			[
				'<img src="/extensions-ucp/v2/v2/C/x.png"',
				'<img src="fandom.com/extensions-ucp/mw139/v2/C/x.png"',
			],
			[
				$fullNotification,
				$expectedFullNotification,
			],
		];
	}
}
