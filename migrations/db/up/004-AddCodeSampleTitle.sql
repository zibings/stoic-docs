/* Optional display title for a code sample, e.g. the file name shown in the sample card header ("user.ts"). */
ALTER TABLE `DocCodeSample` ADD COLUMN `Title` VARCHAR(256) NULL AFTER `SampleKey`;
