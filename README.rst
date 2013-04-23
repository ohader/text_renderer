Alternative TYPO3 GIFBUILDER TEXT RENDERER
==========================================

Example
-------

50 = TEXT_RENDERER
50 {
	text.current = 1

	result {
		format = png
		antialias = 1
	}

	background {
		color = #999999
		transparent = #999999
	}

	font {
		color = #000000
		file = {$plugin.tx_gmkmasquerade.font.filePath}
		size = {$plugin.tx_gmkmasquerade.font.renderingSize}
	}

	renderObj = COA
	renderObj {
		50 = IMAGE
		50 {
			file.import.data = register:TextRendererResult
		}
	}
}
