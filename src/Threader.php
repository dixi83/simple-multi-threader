<?php
/**
 * @package   simple-multi-threader
 * @author    Mahesh S Warrier <maheshs60@gmail.com>
 * @copyright Copyright &copy; Mahesh S Warrier, 2020
 * @version   1.0.0
 */
namespace cs\simplemultithreader;
use Closure;
use COM;
use Composer\Autoload\ClassLoader;
use Opis\Closure\SerializableClosure;
use ReflectionClass;
use function Opis\Closure\{serialize as s};

/**
 * Class Threader
 * @package codespede\simple-multi-threader
 */
class Threader{

    /**
     * @var string Arguments for the closure
     */
	public $arguments;

    /**
     * @var string Directory where jobs will be saved
     */
	public $jobsDir = "smt-jobs";

    /**
     * @var string Directory where logs will be saved
     */
	public $logsDir = "smt-logs";

    /**
     * @var boolean Whether to ignore the HUP (hangup) signal in unix based systems
     */
	public $nohup = true;

	/**
     * @var boolean|integer Whether to use the nice command to determine the program scheduling, default=false, set the nice level by giving the one a number between -20 and 19 (https://linux.die.net/man/1/nice)
     */
	public $nice = false;

    /**
     * @var boolean if 'returnPid' is set true, an array with the pid and the jobId is returned by the thread() method. Example: [ 'pid' => 100, 'jobId' => "862060994a125e64989ad3878fd97a52" ]
     */
    public $returnPid = false;

    /**
     * @var string Fully qualified class name of the Helper to be used
     */
    public $helperClass = "cs\\simplemultithreader\\CommandHelper";

    /**
     * Threader constructor.
     * @param array $config
     */
	public function __construct($config = []){
        if (!empty($config)) {
            self::configure($this, $config);
        }
        $this->init();
    }

    /**
     * Threader initializer.
     */
    public function init(){
        $basePath = $this->getAppBasePath();
        if(!file_exists($basePath."/".$this->jobsDir)) {
            if (!is_link($basePath . "/" . $this->jobsDir)) {
                mkdir($basePath . "/" . $this->jobsDir, 0777);
            }
        }
        if(!file_exists($basePath."/".$this->logsDir)) {
            if (!is_link($basePath . "/" . $this->logsDir))
                mkdir($basePath . "/" . $this->logsDir, 0777);
        }
    }

    /**
     * Execute the given closure in a separate process.
     * @param Closure $closure
     * @return array
     */
    public function thread(Closure $closure){
        $jobId = md5(uniqid(rand(), true));
        $jobsDir = $this->getAppBasePath()."/".$this->jobsDir;
        file_put_contents("{$jobsDir}/{$jobId}_closure.ser", serialize(new SerializableClosure($closure)));
        file_put_contents("{$jobsDir}/{$jobId}_arguments.ser", s($this->arguments));

        $command = "php '".str_replace('\\', '/', __DIR__)."/thread.php' '{$jobId}' '{$this->jobsDir}' '{$this->logsDir}' '{$this->helperClass}'";

        if(!self::isWindows()){
            $command = ($this->nice !== false? "nice -n$this->nice " : "") . ($this->nohup? "nohup " : "") . "{$command} > /dev/null 2>&1 & echo $!";
            $pid = shell_exec($command);
            if ($pid!==null) {
                $result = explode("\n", $pid);
                $return['pid'] = intval($result[0]);
            }
        }
        elseif(self::isWindows()){
            $WshShell = new COM("WScript.Shell");
            $WshShell->Run($command. ' (Get-WmiObject Win32_Process -Filter ProcessId=$PID).ParentProcessId', 0, false);
        }
        $return['jobId'] = $jobId;
        return $return;
    }

    /**
     * Check whether the current environement is Windows or not.
     */
    private static function isWindows(){
	return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Configure the threader object with given properties.
     * @param Threader $object
     * @param array $properties
     * @return Threader
     */
    private static function configure(Threader $object, array $properties){
        foreach ($properties as $name => $value) {
            $object->$name = $value;
        }
        return $object;
    }

    /**
     * Get the base path of the application
     * @return string
     */
    private function getAppBasePath(){
        $reflection = new ReflectionClass(ClassLoader::class);
        return dirname(dirname($reflection->getFileName()),2);
    }
}
