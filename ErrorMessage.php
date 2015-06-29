
<?php
class ErrorMessage{
	//domain check error messages
	const ERROR_DOMAIN_NAME_TAKEN                      = 1;
	const ERROR_DOMAIN_BAD_NAME                        = 2;
	const ERROR_DOMAIN_IS_EMPTY                        = 3;
	const ERROR_DOMAIN_TOO_LONG                        = 4;
	const ERROR_DOMAIN_TOO_SHORT	                   = 5;
	const ERROR_DOMAIN_INVALID_CHAR                    = 17;

	//create directory error messages
	const ERROR_FAIL_FOLDER                            = 6;
	const ERROR_FAIL_CREATE_UPLOAD                     = 7;
	const ERROR_FAIL_CREATE_CACHE                      = 8;


	//install new wiki error messages 
	const ERROR_FAIL_EXE_INSTALL_CMD                   = 9;

	//update database error message 
	const ERROR_DATABASE_SCRIPT_ERROR                  = 10;
        
        //User Not Login 
        const ERROR_NOT_LOG_IN                             = 11;
        
        //Revoke errors
        const ERROR_REMOVE_DIR                             = 12;
        const ERROR_REVOKE_INSTALL                         = 13;
        
        //DB errors;
        //to-do: add more db errors and modify the db class 
        //using pdd. http://www.binpress.com/tutorial/using-php-with-mysql-the-right-way/17
        const ERROR_DB_CONNECT                             = 14;
        
        
        //INVITATION CODE ERROR
        const INV_NOT_FOUND                                = 15;
        const INV_USED                                     = 16;
        
        
        
        
}
?>