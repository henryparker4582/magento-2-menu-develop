<?php

declare(strict_types=1);

namespace Snowdog\Menu\Model\ImportExport;

use Exception;
use LogicException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Filesystem;
use Magento\Framework\HTTP\Adapter\FileTransferFactory;
use Magento\Framework\Validation\ValidationException;
use Magento\ImportExport\Helper\Data as ImportExportHelper;
use Magento\ImportExport\Model\Import as ImportModel;
use Magento\MediaStorage\Model\File\UploaderFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FileUpload
{
    const ENTITY = 'snowdog_menu';

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    private $varDirectory;

    /**
     * @var FileTransferFactory
     */
    private $fileTransferFactory;

    /**
     * @var ImportExportHelper
     */
    private $importExportHelper;

    /**
     * @var ImportModel
     */
    private $import;

    /**
     * @var UploaderFactory
     */
    private $uploaderFactory;

    /**
     * @var Yaml
     */
    private $yaml;

    public function __construct(
        Filesystem $filesystem,
        FileTransferFactory $fileTransferFactory,
        ImportExportHelper $importExportHelper,
        ImportModel $import,
        UploaderFactory $uploaderFactory,
        Yaml $yaml
    ) {
        $this->varDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->fileTransferFactory = $fileTransferFactory;
        $this->importExportHelper = $importExportHelper;
        $this->import = $import;
        $this->uploaderFactory = $uploaderFactory;
        $this->yaml = $yaml;
    }

    /**
     * @throws LogicException
     */
    public function uploadFileAndGetData(): array
    {
        $sourceFile = $this->uploadSource();
        $sourceFilePath = $this->varDirectory->getRelativePath($sourceFile);

        try {
            $stream = $this->varDirectory->openFile($sourceFilePath, 'r');
        } catch (FileSystemException $exception) {
            throw new LogicException(__('Unable to open uploaded file.'));
        }

        $data = '';
        while (!$stream->eof()) {
            $data .= $stream->read(1024);
        }

        $stream->close();
        $this->varDirectory->delete($sourceFilePath);

        return $this->yaml->parse($data);
    }

    /**
     * @throws ValidatorException
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    private function uploadSource(): string
    {
        $fileTransferAdapter = $this->fileTransferFactory->create();

        if (!$fileTransferAdapter->isValid(ImportModel::FIELD_NAME_SOURCE_FILE)) {
            $errors = $fileTransferAdapter->getErrors();

            $errorMessage = $errors[0] === \Zend_Validate_File_Upload::INI_SIZE
                ? $this->importExportHelper->getMaxUploadSizeMessage()
                : __('The file was not uploaded.');

            throw new ValidatorException($errorMessage);
        }

        $uploader = $this->uploaderFactory->create(['fileId' => ImportModel::FIELD_NAME_SOURCE_FILE]);
        $uploader->setAllowedExtensions(Yaml::FILE_EXTENSIONS);
        $uploader->skipDbProcessing(true);

        $workingDir = $this->import->getWorkingDir();

        try {
            $fileName = self::ENTITY . '-' . hash('sha256', microtime()) . '.' . $uploader->getFileExtension();
            $result = $uploader->save($workingDir, $fileName);
        } catch (ValidationException $exception) {
            throw new ValidatorException(__($exception->getMessage()));
        } catch (Exception $exception) {
            throw new ValidatorException(__('The file cannot be uploaded.'));
        }

        return $result['path'] . $result['file'];
    }
}
