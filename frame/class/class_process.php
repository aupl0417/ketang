<?php

class process {

    private $size;
    private $curSize;
    private $producer;
    private $worker;
    private $queueName;

    /**
     * 构造函数
     * @param $work 需要创建的消费者类名
     * @param int $size 最大子进程数量
     * @param $producer 需要创建的消费者类名
     */
    public function __construct($size = 10) {
//        $this->producer = new $produc();
//        $this->worker = new $work();
        $this->size = $size;
        $this->cursize = 0;
    }

    public function start() {
        $producerPid = pcntl_fork();
        if ($producerPid == -1) {
            die("could not fork");
        } elseif ($producerPid) {// parent
//            while (true) {
//                $pid = pcntl_fork();
//                if ($pid == -1) {
//                    die("could not fork");
//                } elseif
//                ($pid) {// parent
//                    $this->cursize++;
//                    if ($this->cursize >= $this->size) {
//                        $sunPid = pcntl_wait($status);
//                        $this->cursize--;
//                    }
//                } else {// worker
//                    //$this->worker->run();
//                    job::worker();
//                    exit();
//                }
//            }
            for ($i = 0; $i < $this->size; $i++) {
                $pids[$i] = pcntl_fork(); // 产生子进程，而且从当前行之下开试运行代码，而且不继承父进程的数据信息
                if ($pids[$i] == -1) {
                    echo "couldn't fork" . "\n";
                } elseif (!$pids[$i]) {
                    job::worker($i);
                    echo "第" . $i . "个进程 -> " . time() . "结束\n";
                    exit(0); //子进程要exit否则会进行递归多进程，父进程不要exit否则终止多进程
                } else {
                    pcntl_waitpid($pids[$i], $status, WNOHANG);
                    echo "wait $i -> " . time() . "status:$status\n";
                }
            }
        } else {// producer
            //$this->producer->run();
            job::producer();
            exit();
        }
    }

}
