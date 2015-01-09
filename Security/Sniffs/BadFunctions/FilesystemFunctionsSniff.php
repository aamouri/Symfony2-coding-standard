<?php


class Security_Sniffs_BadFunctions_FilesystemFunctionsSniff implements PHP_CodeSniffer_Sniff  {
	/**
	* Returns the token types that this sniff is interested in.
	*
	* @return array(int)
	*/
	public function register() {
		return array(T_STRING);
	}

	/**
	* Processes the tokens that this sniff is interested in.
	*
	* @param PHP_CodeSniffer_File $phpcsFile The file where the token was found.
	* @param int                  $stackPtr  The position in the stack where
	*                                        the token was found.
	*
	* @return void
	*/
	public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
		$utils = Security_Sniffs_UtilsFactory::getInstance();

		$tokens = $phpcsFile->getTokens();
		if (in_array($tokens[$stackPtr]['content'], $utils::getFilesystemFunctions())) {
			if ($tokens[$stackPtr]['content'] == 'symlink') {
				$phpcsFile->addWarning('Allowing symlink() while open_basedir is used is actually a security risk. Disabled by default in Suhosin >= 0.9.6', $stackPtr, 'WarnSymlink'); 
			}
            $s = $stackPtr + 1;
			$opener = $phpcsFile->findNext(T_OPEN_PARENTHESIS, $stackPtr, null, false, null, true);
			if (!$opener) {
				// No opener found, so it's probably not a function call
				if (PHP_CodeSniffer::getConfigData('ParanoiaMode')) {
					$phpcsFile->addWarning('Filesystem function ' . $tokens[$stackPtr]['content'] . ' used but not as a function', $stackPtr, 'WarnWeirdFilesystem');
				}
				return;
			}

			$closer = $tokens[$opener]['parenthesis_closer'];
			$s = $phpcsFile->findNext(array_merge(PHP_CodeSniffer_Tokens::$emptyTokens, PHP_CodeSniffer_Tokens::$bracketTokens, Security_Sniffs_Utils::$staticTokens), $s, $closer, true);
            if ($s) {
				$msg = 'Filesystem function ' . $tokens[$stackPtr]['content'] . '() detected with dynamic parameter';
				if ($utils::is_token_user_input($tokens[$s])) {
					$phpcsFile->addError($msg . ' directly from user input', $stackPtr, 'ErrFilesystem');
				} else {
					$phpcsFile->addNotice($msg, $stackPtr, 'WarnFilesystem');
				}
			}
		}
	}

}

?>