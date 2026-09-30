/*

	Documentation-site tables. One install documents one library, so there is no library identifier anywhere.

		- DocVersion: Released versions of the documented library; SortKey orders them without assuming semver
		- DocModule: Logical groupings of symbols (namespaces, packages, files), identified by a path string
		- DocSymbol: A documented symbol (function, class, method, option, error, ...) with optional parent symbol
		- DocSymbolVersion: The contract of a symbol valid from IntroducedVersionID until RemovedVersionID (exclusive, nullable)
		- DocPage: Prose pages in one of the four reader modes (learn, do, reference, explain), valid over a version range
		- DocPageSymbol: Links pages to the symbols they are about (subject) or mention (drives contract-pane follow)
		- DocCodeSample: Context-rendered code samples for a page, keyed by sample key + language + variant
		- DocChange: Entries in the changelog for a version, with before/after code and optional codemod
		- DocChangeSymbol: Links changes to the symbols they affect
		- DocCourse / DocLesson / DocLessonStep: Learn mode courses, their ordered lessons (each a learn-mode page), and steps
		- DocContextOption: Languages and package managers offered in the context bar

*/

CREATE TABLE IF NOT EXISTS `DocVersion` (
    `ID` INT AUTO_INCREMENT NOT NULL,
    `Tag` VARCHAR(64) NOT NULL,
    `Label` VARCHAR(64) NOT NULL,
    `SortKey` INT NOT NULL,
    `ReleasedAt` DATETIME NULL,
    `IsLatest` TINYINT NOT NULL DEFAULT 0,
    `IsSupported` TINYINT NOT NULL DEFAULT 1,
    PRIMARY KEY (`ID`),
    CONSTRAINT `UQ_DocVersionTag` UNIQUE (`Tag`),
    CONSTRAINT `UQ_DocVersionLabel` UNIQUE (`Label`),
    CONSTRAINT `UQ_DocVersionSortKey` UNIQUE (`SortKey`)
);

CREATE TABLE IF NOT EXISTS `DocModule` (
    `ID` INT AUTO_INCREMENT NOT NULL,
    `Path` VARCHAR(256) NOT NULL,
    `Summary` VARCHAR(1024) NOT NULL DEFAULT '',
    `SortOrder` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`ID`),
    CONSTRAINT `UQ_DocModulePath` UNIQUE (`Path`)
);

CREATE TABLE IF NOT EXISTS `DocSymbol` (
    `ID` INT AUTO_INCREMENT NOT NULL,
    `ModuleID` INT NOT NULL,
    `ParentSymbolID` INT NULL,
    `Name` VARCHAR(256) NOT NULL,
    `Kind` VARCHAR(32) NOT NULL,
    `SortOrder` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`ID`),
    INDEX `IX_DocSymbolModule` (`ModuleID`, `ParentSymbolID`, `Name`),
    CONSTRAINT `FK_DocSymbolModule` FOREIGN KEY (`ModuleID`) REFERENCES `DocModule` (`ID`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `FK_DocSymbolParent` FOREIGN KEY (`ParentSymbolID`) REFERENCES `DocSymbol` (`ID`) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `DocSymbolVersion` (
    `ID` INT AUTO_INCREMENT NOT NULL,
    `SymbolID` INT NOT NULL,
    `IntroducedVersionID` INT NOT NULL,
    `RemovedVersionID` INT NULL,
    `Signature` TEXT NOT NULL,
    `Summary` VARCHAR(1024) NOT NULL DEFAULT '',
    `Status` VARCHAR(32) NOT NULL DEFAULT 'stable',
    `ParamsJson` JSON NOT NULL,
    `ReturnsJson` JSON NOT NULL,
    `ThrowsJson` JSON NOT NULL,
    `SourcePath` VARCHAR(1024) NULL,
    `SourceLine` INT NULL,
    PRIMARY KEY (`ID`),
    INDEX `IX_DocSymbolVersionSymbol` (`SymbolID`, `IntroducedVersionID`),
    CONSTRAINT `FK_DocSymbolVersionSymbol` FOREIGN KEY (`SymbolID`) REFERENCES `DocSymbol` (`ID`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `FK_DocSymbolVersionIntroduced` FOREIGN KEY (`IntroducedVersionID`) REFERENCES `DocVersion` (`ID`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `FK_DocSymbolVersionRemoved` FOREIGN KEY (`RemovedVersionID`) REFERENCES `DocVersion` (`ID`) ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS `DocPage` (
    `ID` INT AUTO_INCREMENT NOT NULL,
    `Mode` VARCHAR(16) NOT NULL,
    `Slug` VARCHAR(256) NOT NULL,
    `Title` VARCHAR(512) NOT NULL,
    `Summary` VARCHAR(1024) NOT NULL DEFAULT '',
    `Body` MEDIUMTEXT NOT NULL,
    `IntroducedVersionID` INT NOT NULL,
    `RemovedVersionID` INT NULL,
    `Minutes` INT NULL,
    `Created` DATETIME NOT NULL,
    `Updated` DATETIME NOT NULL,
    PRIMARY KEY (`ID`),
    CONSTRAINT `UQ_DocPageModeSlugVersion` UNIQUE (`Mode`, `Slug`, `IntroducedVersionID`),
    CONSTRAINT `FK_DocPageIntroduced` FOREIGN KEY (`IntroducedVersionID`) REFERENCES `DocVersion` (`ID`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `FK_DocPageRemoved` FOREIGN KEY (`RemovedVersionID`) REFERENCES `DocVersion` (`ID`) ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS `DocPageSymbol` (
    `PageID` INT NOT NULL,
    `SymbolID` INT NOT NULL,
    `Role` VARCHAR(16) NOT NULL,
    PRIMARY KEY (`PageID`, `SymbolID`),
    CONSTRAINT `FK_DocPageSymbolPage` FOREIGN KEY (`PageID`) REFERENCES `DocPage` (`ID`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `FK_DocPageSymbolSymbol` FOREIGN KEY (`SymbolID`) REFERENCES `DocSymbol` (`ID`) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `DocCodeSample` (
    `ID` INT AUTO_INCREMENT NOT NULL,
    `PageID` INT NOT NULL,
    `SampleKey` VARCHAR(64) NOT NULL,
    `Language` VARCHAR(32) NOT NULL,
    `Variant` VARCHAR(32) NULL,
    `Code` TEXT NOT NULL,
    `IsTested` TINYINT NOT NULL DEFAULT 0,
    `LastTestPassVersionID` INT NULL,
    PRIMARY KEY (`ID`),
    INDEX `IX_DocCodeSamplePage` (`PageID`, `SampleKey`, `Language`, `Variant`),
    CONSTRAINT `FK_DocCodeSamplePage` FOREIGN KEY (`PageID`) REFERENCES `DocPage` (`ID`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `FK_DocCodeSampleTestVersion` FOREIGN KEY (`LastTestPassVersionID`) REFERENCES `DocVersion` (`ID`) ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS `DocChange` (
    `ID` INT AUTO_INCREMENT NOT NULL,
    `VersionID` INT NOT NULL,
    `Kind` VARCHAR(16) NOT NULL,
    `Title` VARCHAR(512) NOT NULL,
    `Why` TEXT NOT NULL,
    `RfcUrl` VARCHAR(1024) NULL,
    `BeforeCode` TEXT NULL,
    `AfterCode` TEXT NULL,
    `CodemodCmd` VARCHAR(512) NULL,
    `SortOrder` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`ID`),
    INDEX `IX_DocChangeVersion` (`VersionID`, `Kind`),
    CONSTRAINT `FK_DocChangeVersion` FOREIGN KEY (`VersionID`) REFERENCES `DocVersion` (`ID`) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `DocChangeSymbol` (
    `ChangeID` INT NOT NULL,
    `SymbolID` INT NOT NULL,
    PRIMARY KEY (`ChangeID`, `SymbolID`),
    CONSTRAINT `FK_DocChangeSymbolChange` FOREIGN KEY (`ChangeID`) REFERENCES `DocChange` (`ID`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `FK_DocChangeSymbolSymbol` FOREIGN KEY (`SymbolID`) REFERENCES `DocSymbol` (`ID`) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `DocCourse` (
    `ID` INT AUTO_INCREMENT NOT NULL,
    `Slug` VARCHAR(256) NOT NULL,
    `Title` VARCHAR(512) NOT NULL,
    `Summary` VARCHAR(1024) NOT NULL DEFAULT '',
    `SortOrder` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`ID`),
    CONSTRAINT `UQ_DocCourseSlug` UNIQUE (`Slug`)
);

CREATE TABLE IF NOT EXISTS `DocLesson` (
    `ID` INT AUTO_INCREMENT NOT NULL,
    `CourseID` INT NOT NULL,
    `PageID` INT NOT NULL,
    `Ordinal` INT NOT NULL,
    PRIMARY KEY (`ID`),
    CONSTRAINT `UQ_DocLessonOrdinal` UNIQUE (`CourseID`, `Ordinal`),
    CONSTRAINT `FK_DocLessonCourse` FOREIGN KEY (`CourseID`) REFERENCES `DocCourse` (`ID`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `FK_DocLessonPage` FOREIGN KEY (`PageID`) REFERENCES `DocPage` (`ID`) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `DocLessonStep` (
    `ID` INT AUTO_INCREMENT NOT NULL,
    `LessonID` INT NOT NULL,
    `Ordinal` INT NOT NULL,
    `Prompt` TEXT NOT NULL,
    `Hint` TEXT NULL,
    `Answer` TEXT NULL,
    `ExpectedOutput` TEXT NULL,
    PRIMARY KEY (`ID`),
    CONSTRAINT `UQ_DocLessonStepOrdinal` UNIQUE (`LessonID`, `Ordinal`),
    CONSTRAINT `FK_DocLessonStepLesson` FOREIGN KEY (`LessonID`) REFERENCES `DocLesson` (`ID`) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `DocContextOption` (
    `ID` INT AUTO_INCREMENT NOT NULL,
    `Kind` VARCHAR(32) NOT NULL,
    `OptionKey` VARCHAR(64) NOT NULL,
    `Label` VARCHAR(128) NOT NULL,
    `SortOrder` INT NOT NULL DEFAULT 0,
    `IsDefault` TINYINT NOT NULL DEFAULT 0,
    PRIMARY KEY (`ID`),
    CONSTRAINT `UQ_DocContextOptionKindKey` UNIQUE (`Kind`, `OptionKey`)
);
