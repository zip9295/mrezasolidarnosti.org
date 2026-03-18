<?php
namespace Solidarity\Transaction\Service;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Solidarity\Transaction\Repository\TransactionRepository;
use Skeletor\Core\TableView\Service\TableView;
use Psr\Log\LoggerInterface as Logger;
use Skeletor\User\Service\Session;
use Solidarity\Transaction\Filter\Transaction as TransactionFilter;

class Transaction extends TableView
{
    /**
     * @param TransactionRepository $repo
     * @param Session $user
     * @param Logger $logger
     */
    public function __construct(
        TransactionRepository $repo, Session $user, Logger $logger, TransactionFilter $filter, private Project $project,
    ) {
        parent::__construct($repo, $user, $logger, $filter);
    }

    public function getTransactionsBySchool($schoolId)
    {
        return $this->repo->getTransactionsBySchool($schoolId);
    }

    public function perPersonLimit($donorEmail, $receiverName)
    {
        return $this->repo->perPersonLimit($donorEmail, $receiverName);
    }

    public function prepareEntities($entities)
    {
        $this->repo->resolveBeneficiaries($entities);

        $items = [];
        /* @var \Solidarity\Transaction\Entity\Transaction $transaction */
        foreach ($entities as $transaction) {
            $beneficiaryName = $transaction->beneficiary->name ?? 'N/A';
            if ($transaction->beneficiary->school) {
                $beneficiaryName .= '<br />' . $transaction->beneficiary->school->name
                    . '<br />' . $transaction->beneficiary->school->city->name;
            }

            $itemData = [
                'id' => $transaction->getId(),
                'accountNumber' =>  [
                    'value' => $transaction->accountNumber,
                    'editColumn' => true,
                ],
                'status' => \Solidarity\Transaction\Entity\Transaction::getHrStatuses()[$transaction->status],
                'amount' => $transaction->amount,
                'email' => $transaction->donor->firstName .' '. $transaction->donor->lastName .'<br />'. $transaction->donor->email,
                'name' => $beneficiaryName,
                'project' => $transaction->project->code,
                'createdAt' => $transaction->getCreatedAt()->format('d.m.Y'),
//                'updatedAt' => $transaction->getUpdatedAt()->format('d.m.Y'),
            ];
            $items[] = [
                'columns' => $itemData,
                'id' => $transaction->getId(),
            ];
        }
        return $items;
    }

    public function compileTableColumns()
    {
        // @TODO add filter per school, search per donor/educator details
        $columnDefinitions = [
            ['name' => 'email', 'label' => 'Email'],
            ['name' => 'name', 'label' => 'Ime'],
            ['name' => 'accountNumber', 'label' => 'Br računa'],
            ['name' => 'amount', 'label' => 'Iznos'],
            ['name' => 'status', 'label' => 'Status', 'filterData' => \Solidarity\Transaction\Entity\Transaction::getHrStatuses()],
            ['name' => 'project', 'label' => 'Projekat', 'filterData' => $this->project->getFilterData()],
            ['name' => 'createdAt', 'label' => 'Datum'],
        ];

        return $columnDefinitions;
    }

    public function compileXlsxTransactionList($transactions, $school)
    {
        $spreadsheet = new Spreadsheet();
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->getSpreadsheet()->getProperties()
            ->setCreator("MS")
            ->setLastModifiedBy("MS");
        $writer->getSpreadsheet()->getDefaultStyle()->getAlignment()->setWrapText(true);
        $sheet = $writer->getSpreadsheet()->getActiveSheet();

        $sheet->getCell('A1')->setValue('#');
        $sheet->getCell('B1')->setValue('Ime oštećenog');
        $sheet->getCell('C1')->setValue('Iznos');
        $sheet->getCell('D1')->setValue('Broj računa');
        $sheet->getCell('E1')->setValue('Izaberi status');
        foreach (['A', 'B', 'C', 'D', 'E'] as $letter) {
            $sheet->getColumnDimension($letter)->setAutoSize(true);
        }
        $sheet->getColumnDimension('E')->setAutoSize(false);
        $sheet->getColumnDimension('E')->setWidth(20);
        $row = 2;
        foreach ($transactions as $transaction) {
            $row++;
            $sheet->getCell('A' . $row)->setValue($transaction->id);
            $sheet->getCell('B' . $row)->setValue($transaction->name);
            $sheet->getCell('C' . $row)->setValue($transaction->amount);
            $sheet->getCell('D' . $row)->setValue($transaction->accountNumber .' ');

            $sheet->getCell('E'.$row)->getDataValidation()
                ->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                ->setAllowBlank(false)
                ->setShowInputMessage(true)
                ->setPrompt('Izaberi status')
                ->setShowDropDown(true)
                ->setShowErrorMessage(true)
                ->setFormula1('"Plaćeno,Neplaćeno"');
            $sheet->getCell('E'.$row)->getStyle()
                ->getBorders()
                ->getOutline()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK)
                ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLACK));
        }
        $filePath = DATA_PATH . sprintf('/lists/%s.xlsx', str_replace(' ', '', $school));
        $writer->save($filePath);

        return $filePath;
    }
}