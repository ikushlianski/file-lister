
/* USERS */
/* Let us give users little space on purpose - to demonstrate how edge cases in our app work */
/* We make user folder unique by concatenating it with user id via trigger */
INSERT INTO `file_lister`.`user`
(`u_id`, `u_name`, `u_login`, `u_password`, `u_email`, `u_spacelimit`, `u_foldername`, `u_auth_token`)
VALUES (NULL, 'Ilya', 'root1', '$2y$10$yB7rbjmWEnTmqOmRbdu7nulASFYntCqFJEVI2HVwuw66Jcjc/ZIH2', 'ilya@gmail.com', 1024000000, NULL, '');
INSERT INTO `file_lister`.`user`
(`u_id`, `u_name`, `u_login`, `u_password`, `u_email`, `u_spacelimit`, `u_foldername`, `u_auth_token`)
VALUES  (NULL, 'Roger', 'root2', '$2y$10$NlPpenEnemkI8eiS/8ww5uHcBjn5LP.StXoMVFaG4lq6gU/WwRsnq', 'roger@gmail.com', 512000000, NULL, '');

/* EXTENSIONS */
INSERT INTO `file_lister`.`extension` (`ext_id`, `ext_name`)
VALUES (NULL, 'jpg'), (NULL, 'png'), (NULL, 'zip'), (NULL, '');


/* EXTENSIONS AND FILE LIMITS PER USER */
INSERT INTO `file_lister`.`users_extensions` (`u_id`, `ext_id`, `u_ext_maxsize`)
VALUES (1, 1, 3000000), (1, 2, 2000000), (1, 3, 100000000), (1, 4, 5000000),
  (2, 1, 3000000), (2, 2, 2000000), (2, 3, 100000000), (2, 4, 5000000);

/* FILES */
INSERT INTO `file_lister`.`file` VALUES(NULL, 'catclicker-img.jpg', '567cf6bb61d26cd4824ee271d1bf786ceb426928', 45704, 1523705454, 1, 1);
INSERT INTO `file_lister`.`file` VALUES(NULL, 'php_openssl.dll.zip', '7d4ca4024040348593a631d7e2e13b7cf4cb7e62', 16527, 1523705688, 2, 3);

/* SITE CONFIG */
INSERT INTO `file_lister`.`config` (`setting_id`, `setting_key`, `setting_val`)
VALUES
  (NULL, 'MAX_BASENAME_LENGTH', 745),
  (NULL, 'MAX_EXTENSION_LENGTH', 255),
  (NULL, 'REMEMBER_ME_DURATION', 1209600); # 2 weeks

/* MESSAGES */
INSERT INTO `file_lister`.`message` (`msg_id`, `msg_code`, `msg_text_en`)
VALUES
(NULL, 'FILE_DELETION_SUCCESS', 'Deleted successfully'),
(NULL, 'FILE_DELETION_ERROR', 'Error while deleting'),
(NULL, 'FILE_DOWNLOAD_SUCCESS', 'You successfully downloaded the file'),
(NULL, 'FILE_DOWNLOAD_ERROR', 'File could not be downloaded'),
(NULL, 'FILE_UPLOAD_SUCCESS', 'Your file has been uploaded successfully'),
(NULL, 'FILE_UPLOAD_ERROR', 'Your file could not be uploaded'),
(NULL, 'FILE_EXISTS_ERROR', 'This file already exists in your folder'),
(NULL, 'FILE_UPLOAD_ERROR_OUT_OF_SPACE', 'You do not have enough space for this file'),
(NULL, 'FILE_UPLOAD_ERROR_FORMAT_MISMATCH', 'Files of this format are not allowed'),
(NULL, 'FILE_UPLOAD_ERROR_FORMAT_SIZE_MISMATCH', 'File with this extension exceeds permitted limit'),
(NULL, 'LOGIN_ERROR', 'Please enter valid login and password'),
(NULL, 'ERROR_404', 'I tried hard, but could not find anything like that...'),
(NULL, 'ERROR_500', 'Some server error has occurred.');