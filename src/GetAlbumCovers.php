<?php

declare(strict_types=1);

namespace Invoke;

use Exception;
use Intervention\Image\ImageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;

final class GetAlbumCovers extends Command
{
    public function configure(): void
    {
        $this->setName('poopboks:scrape');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $url = 'https://www.popboks.com/dzuboksimages';
        $client = HttpClient::create();
        $manager = new ImageManager();
        $numbers = range(1, 194);
        $parts = [
            '2_0_0', '2_1_0', '2_2_0', '2_3_0', '2_4_0',
            '2_0_1', '2_1_1', '2_2_1', '2_3_1', '2_4_1',
            '2_0_2', '2_1_2', '2_2_2', '2_3_2', '2_4_2',
            '2_0_3', '2_1_3', '2_2_3', '2_3_3', '2_4_3',
            '2_0_4', '2_1_4', '2_2_4', '2_3_4', '2_4_4',
            '2_0_5', '2_1_5', '2_2_5', '2_3_5', '2_4_5',
            '2_0_6', '2_1_6', '2_2_6', '2_3_6', '2_4_6',
        ];

        $io->title('Scraping album images from www.popboks.com.');

        foreach ($numbers as $number) {
            $io->text(sprintf('Reading DŽUBOKS Broj %s.', $number));

            try {
                $imageParts = [];
                $imagePartsRequests = [];

                for ($i = 0; $i < count($parts); $i++) {
                    $imagePartsRequests[$i] = $client->request(
                        'GET',
                        sprintf('%s/%s/1/%s.jpg', $url, $number, $parts[$i])
                    );
                }
                for ($i = 0; $i < count($parts); $i++) {
                    $imageParts[$i] = $imagePartsRequests[$i]->getContent();
                }
            } catch (Exception $exception) {
                $io->warning(sprintf('Skipping number %s', $number));
                $io->warning(sprintf('Going to next folder.'));

                continue;
            }

            $imageParts = array_map(fn(string $part) => $manager->make($part), $imageParts);

            $canvasWidth = 0;
            foreach (range(0, 4) as $row) {
                $canvasWidth += $imageParts[$row]->getWidth();
            }

            $canvasHeight = 0;
            foreach ([0, 5, 10, 15, 20, 25, 30] as $i) {
                $canvasHeight += $imageParts[$i]->getHeight();
            }

            $canvas = $manager->canvas($canvasWidth, $canvasHeight);

            $x = 0;
            $y = 0;
            $wasLastInRow = false;

            foreach ($imageParts as $i => $part) {
                $prev = $i - 1;
                if (isset($wasLastInRow) && $wasLastInRow) {
                    $x = 0;
                    $y += $imageParts[$prev]->getHeight();
                } else {
                    $x += isset($imageParts[$prev]) ? $imageParts[$prev]->getWidth(): 0;
                }

                $canvas->insert($imageParts[$i], 'top-left', $x, $y);

                $wasLastInRow = ($i + 1) % 5 === 0;
            }

            $path = __DIR__ . "/../export/{$number}.jpg";
            $canvas->save($path);
            $io->text(sprintf('Saved DŽUBOKS Broj %s. at.', $number));
        }

        $io->success('Finished scraping images. Go see my nice and hard work.');

        return 1;
    }
}
