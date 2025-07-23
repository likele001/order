<?php

namespace app\admin\controller\scanwork;

use app\common\controller\Backend;
use app\admin\model\scanwork\Allocation;
use app\admin\model\scanwork\Qrcode as QrcodeModel;
use think\Exception;
use think\Log;
use Endroid\QrCode\QrCode as EndroidQrCode;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Color\Color;


/**
 * 二维码管理
 */
class Qrcode extends Backend
{
    /**
     * 生成二维码（单个）
     */
    public function generate($allocationId = null)
    {
        try {
            // 1. 验证参数
            if (empty($allocationId) || !is_numeric($allocationId)) {
                throw new \Exception("无效的allocationId: " . var_export($allocationId, true));
            }
            $allocationId = (int)$allocationId;

            // 2. 获取分配记录
            $allocation = Allocation::with(['order', 'model.product', 'process', 'user'])
                ->where('id', $allocationId)
                ->find();

            if (!$allocation) {
                throw new \Exception("分配记录不存在，ID: {$allocationId}");
            }

            $this->validateAllocationRelations($allocation);

            // 3. 生成二维码内容为报工链接
            $qrContent = 'http://order.023ent.net/index/worker/report/id/' . $allocation->id . '.html';

            // 4. 生成二维码图片
            $qrImage = self::generateQrCodeImageStatic($qrContent, $allocationId);
            if(!$qrImage) {
                throw new \Exception("二维码图片生成失败");
            }

            \think\Log::info("二维码生成成功，分配ID: {$allocationId}，图片路径: {$qrImage}");

            $this->success('二维码生成成功', null, [
                'allocation' => $allocation,
                'qr_content' => $qrContent,
                'qr_image' => $qrImage
            ]);
        } catch (\Exception $e) {
            \think\Log::error("二维码生成失败: " . $e->getMessage() . "，追踪: " . $e->getTraceAsString());
            $this->error('生成失败: ' . $e->getMessage());
        }
    }

    /**
     * 验证分配记录的关联数据
     */
    private function validateAllocationRelations($allocation)
    {
        if (empty($allocation->order)) {
            throw new Exception("分配记录未关联订单，ID: {$allocation->id}");
        }
        if (empty($allocation->model)) {
            throw new Exception("分配记录未关联模型，ID: {$allocation->id}");
        }
        if (empty($allocation->model->product)) {
            throw new Exception("模型未关联产品，模型ID: {$allocation->model->id}");
        }
        if (empty($allocation->process)) {
            throw new Exception("分配记录未关联工序，ID: {$allocation->id}");
        }
        if (empty($allocation->user)) {
            Log::warning("分配记录未关联用户，ID: {$allocation->id}");
        }
    }

    /**
     * 构建二维码数据
     */
    private function buildQrData($allocation)
    {
        return [
            'type' => 'allocation',
            'id' => $allocation->id,
            'order_no' => $allocation->order->order_no ?? '',
            'product_name' => $allocation->model->product->name ?? '',
            'model_name' => $allocation->model->name ?? '',
            'process_name' => $allocation->process->name ?? '',
            'user_name' => $allocation->user->nickname ?? '未知用户',
            'quantity' => $allocation->quantity ?? 0,
            'remaining' => $allocation->remaining_quantity ?? 0,
            'timestamp' => time()
        ];
    }

    /**
     * 生成二维码图片（静态方法，按日期分目录，内容为报工链接）
     * @param string $content 二维码内容
     * @param int $allocationId
     * @param bool $returnUrl 返回URL还是物理路径
     * @return string
     */
    public static function generateQrCodeImageStatic($content, $allocationId, $returnUrl = true)
    {
        try {
            $dateDir = date('Ymd');
            $qrDir = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'qrcode' . DS . $dateDir;
            if (!is_dir($qrDir)) {
                $created = mkdir($qrDir, 0755, true);
                if (!$created) {
                    throw new \Exception("无法创建目录: {$qrDir}，检查权限");
                }
            }
            if (!is_writable($qrDir)) {
                throw new \Exception("目录不可写: {$qrDir}，检查权限");
            }
            $filename = "allocation_{$allocationId}_" . time() . ".png";
            $filepath = $qrDir . DS . $filename;
            $qrCode = \Endroid\QrCode\QrCode::create($content)
                ->setEncoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
                ->setErrorCorrectionLevel(\Endroid\QrCode\ErrorCorrectionLevel::Low)
                ->setSize(300)
                ->setMargin(10)
                ->setRoundBlockSizeMode(\Endroid\QrCode\RoundBlockSizeMode::Margin)
                ->setForegroundColor(new \Endroid\QrCode\Color\Color(0, 0, 0))
                ->setBackgroundColor(new \Endroid\QrCode\Color\Color(255, 255, 255));
            $writer = new \Endroid\QrCode\Writer\PngWriter();
            $result = $writer->write($qrCode);
            $written = file_put_contents($filepath, $result->getString());
            if ($written === false) {
                throw new \Exception("无法写入文件: {$filepath}");
            }
            return $returnUrl ? '/uploads/qrcode/' . $dateDir . '/' . $filename : $filepath;
        } catch (\Exception $e) {
            \think\Log::error("二维码图片生成失败: " . $e->getMessage());
            return false;
        }
    }

    // 其他方法保持不变...
    /**
     * 二维码列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = QrcodeModel::with(['allocation.order', 'allocation.model.product', 'allocation.process', 'allocation.user'])
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 批量生成二维码
     */
    public function batchGenerate()
    {
        if ($this->request->isPost()) {
            $allocationIds = $this->request->post('allocation_ids');
            if (!$allocationIds) {
                $this->error('请选择要生成二维码的任务');
            }

            $allocations = Allocation::with(['order', 'model.product', 'process', 'user'])
                ->where('id', 'in', $allocationIds)
                ->select();

            $qrCodes = [];
            foreach ($allocations as $allocation) {
                try {
                    $this->validateAllocationRelations($allocation);
                    $qrContent = 'http://order.023ent.net/index/worker/report/id/' . $allocation->id . '.html';
                    $qrImage = self::generateQrCodeImageStatic($qrContent, $allocation->id);
                    $qrCodes[] = [
                        'allocation' => $allocation,
                        'qr_content' => $qrContent,
                        'qr_image' => $qrImage,
                        'status' => 'success'
                    ];
                } catch (\Exception $e) {
                    $qrCodes[] = [
                        'allocation_id' => $allocation->id,
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ];
                    \think\Log::error("批量生成二维码失败，分配ID: {$allocation->id}，错误: " . $e->getMessage());
                }
            }
            $this->success('批量生成完成', null, $qrCodes);
        }
        return $this->view->fetch();
    }

    /**
     * 下载二维码图片
     */
    public function download($allocationId)
    {
        try {
            if (empty($allocationId) || !is_numeric($allocationId)) {
                throw new \Exception("无效的allocationId: " . var_export($allocationId, true));
            }
            $allocationId = (int)$allocationId;

            $allocation = Allocation::with(['order', 'model.product', 'process'])
                ->where('id', $allocationId)
                ->find();

            if (!$allocation) {
                throw new \Exception("分配记录不存在，ID: {$allocationId}");
            }

            $qrContent = 'http://order.023ent.net/index/worker/report/id/' . $allocation->id . '.html';
            $qrImagePath = self::generateQrCodeImageStatic($qrContent, $allocationId, false);

            if (!file_exists($qrImagePath)) {
                throw new \Exception("二维码文件不存在: {$qrImagePath}");
            }

            $filename = "QR_{$allocation->order->order_no}_{$allocation->model->product->name}_{$allocation->model->name}_{$allocation->process->name}.png";
            $filename = preg_replace('/[^\w\.-]/', '_', $filename);

            ob_clean();
            flush();
            header('Content-Type: image/png');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($qrImagePath));
            readfile($qrImagePath);
            exit;
        } catch (\Exception $e) {
            \think\Log::error("二维码下载失败: " . $e->getMessage());
            $this->error('下载失败: ' . $e->getMessage());
        }
    }

    /**
     * 打印二维码标签页面
     */
    public function print($allocationId)
    {
        try {
            if (empty($allocationId) || !is_numeric($allocationId)) {
                throw new \Exception("无效的allocationId: " . var_export($allocationId, true));
            }
            $allocationId = (int)$allocationId;

            $allocation = Allocation::with(['order', 'model.product', 'process', 'user'])
                ->where('id', $allocationId)
                ->find();

            if (!$allocation) {
                throw new \Exception("分配记录不存在，ID: {$allocationId}");
            }

            $qrContent = 'http://order.023ent.net/index/worker/report/id/' . $allocation->id . '.html';
            $qrImage = self::generateQrCodeImageStatic($qrContent, $allocationId);

            $this->view->assign([
                'allocation' => $allocation,
                'qr_image' => $qrImage,
                'qr_content' => $qrContent
            ]);
            return $this->view->fetch();
        } catch (\Exception $e) {
            \think\Log::error("二维码打印页面加载失败: " . $e->getMessage());
            $this->error('加载失败: ' . $e->getMessage());
        }
    }
}
    