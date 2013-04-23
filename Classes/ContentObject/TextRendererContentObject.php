<?php
namespace OliverHader\TextRenderer\ContentObject;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Oliver Hader <oliver.hader@typo3.org>
 *  
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * @package text_renderer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class TextRendererContentObject implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @param string $name
	 * @param array $configuration
	 * @param string $typoScriptKey
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObjectRenderer
	 * @return NULL|string
	 */
	public function cObjGetSingleExt($name, array $configuration, $typoScriptKey, \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObjectRenderer) {
		$text = $configuration['text'];

		if (!empty($configuration['text.'])) {
			$text = $contentObjectRenderer->stdWrap($configuration['text'], $configuration['text.']);
		}

		if (empty($text)) {
			return NULL;
		}

		$parameters = $this->getParameters($text, $configuration);
		$resultFileHash = sha1(serialize($parameters));
		$resultFileName = 'typo3temp/text_renderer_' . $resultFileHash . '.' . $this->getResultFormat($configuration);
		$parameters[] = $resultFileName;

		if (!file_exists(PATH_site . $resultFileName)) {
			$command = \TYPO3\CMS\Core\Utility\GeneralUtility::imageMagickCommand(
				'convert', $this->escapeParameters($parameters)
			);
			\TYPO3\CMS\Core\Utility\CommandUtility::exec($command);
			\TYPO3\CMS\Core\Utility\GeneralUtility::fixPermissions($resultFileName);
		}

		return $this->render($resultFileName, $configuration, $contentObjectRenderer);
	}

	/**
	 * @param string $fileName
	 * @param array $configuration
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObjectRenderer
	 * @return mixed
	 */
	protected function render($fileName, array $configuration, \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObjectRenderer) {
		array_push(
			$this->getFrontend()->registerStack,
			$this->getFrontend()->register
		);

		$this->getFrontend()->register['TextRendererResult'] = $fileName;

		$content = $contentObjectRenderer->cObjGetSingle(
			$configuration['renderObj'],
			$configuration['renderObj.']
		);

		$this->getFrontend()->register = array_pop(
			$this->getFrontend()->registerStack
		);

		return $content;
	}

	/**
	 * @param string $text
	 * @param array $configuration
	 * @return array
	 */
	protected function getParameters($text, array $configuration) {
		$parameters = array();

		if (!empty($configuration['result.']['antialias'])) {
			$parameters['-antialias'] = TRUE;
		}
		if (!empty($configuration['background.']['color'])) {
			$parameters['-background'] = $configuration['background.']['color'];
		}
		if (!empty($configuration['background.']['transparent'])) {
			$parameters['-transparent'] = $configuration['background.']['transparent'];
		}

		$pointSize = 100;
		$parameters['-pointsize'] = $pointSize;
		$parameters['-font'] = $this->getTemplateService()->getFileName($configuration['font.']['file']);
		$parameters['-resize'] = round($configuration['font.']['size'] / $pointSize * 100, 1) . '%';
		$parameters['label:'] = $text;

		return $parameters;
	}

	/**
	 * @param array $parameters
	 * @return string
	 */
	protected function escapeParameters(array $parameters) {
		$escapedParameters = array();

		foreach ($parameters as $key => $value) {
			if (is_numeric($key)) {
				$argument = '';
			} elseif (substr($key, -1) === ':') {
				$argument = $key;
			} else {
				$argument = $key . ' ';
			}

			if (is_bool($value) && $value) {
				$escapedParameters[] = $argument;
			} elseif (!empty($value)) {
				$escapedParameters[] = $argument . escapeshellarg($value);
			}
		}

		return implode(' ', array_map('trim', $escapedParameters)) . '###SkipStripProfile###';
	}

	/**
	 * @param array $configuration
	 * @return string
	 */
	protected function getResultFormat(array $configuration) {
		if (!empty($configuration['result.']['format'])) {
			$resultFormat = $configuration['result.']['format'];
		}

		if (empty($resultFormat)) {
			$resultFormat = 'png';
		}

		return $resultFormat;
	}

	/**
	 * @return \TYPO3\CMS\Core\TypoScript\TemplateService
	 */
	protected function getTemplateService() {
		return $this->getFrontend()->tmpl;
	}

	/**
	 * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected function getFrontend() {
		return $GLOBALS['TSFE'];
	}

}
?>