<?php
	error_reporting(E_ALL);
	set_time_limit(0);
	ob_implicit_flush();

	$socket=new socket('127.0.0.1','8000');
	$socket->run();

	class socket{
		protected $hand;
		public $soc;
		public $socs;
		public function  __construct($address,$port)
		{
			//�����׽���
			$this->soc=$this->createSocket($address,$port);
			$this->socs=array($this->soc);

		}
		public function createSocket()
		{
			$socket= socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
	        socket_bind($socket, '127.0.0.1','8000');
	        socket_listen($socket);
	        return $socket;
		}

		public function run(){
			while(true){
				$arr=$this->socs;
				$write=$except=NULL;
				socket_select($arr,$write,$except, NULL);
				foreach($arr as $k=>$v){
					if($this->soc == $v){
						$client=socket_accept($this->soc);
						if($client <0){
							echo "socket_accept() failed";
						}else{
							// array_push($this->socs,$client);
							// unset($this[]);
							$this->socs[]=$client;
						}
					}else{
						//�������ӵ�socket��������  ���ص��Ǵ�socket�н��յ��ֽ���
						$byte=socket_recv($v, $buff,20480, 0);
						//������յ��ֽ���0 ����
						if($byte<7)
							continue;
						//�ж���û������û���������������,��������� ����д���
						if(!$this->hand[(int)$client]){
							//�������ֲ���
							$this->hands($client,$buff,$v);
						}else{
							//�������ݲ���
							$mess=$this->decodeData($buff);
					// $writes ="\x81".chr(strlen($block[0])).$block[0];
						           	//��������
							$this->send($mess,$v);
							// foreach ($this->socs as $keys => $values) {
				   //         		// $mess['name']="�ο�{$v}";
				   //         		$mess['name']="Tourist{$v}";
				   //         		$str=json_encode($mess);
				   //         		$writes ="\x81".chr(strlen($str)).$str;
				   //         		// if($this->hand[(int)$values])
				   //         			socket_write($values,$writes,strlen($writes));
				   //         	}
						}
					}
				}
			}
		}
		//��������
		public function hands($client,$buff,$v)
		{
			//��ȡwebsocket����key�����м���
			$buf  = substr($buff,strpos($buff,'Sec-WebSocket-Key:')+18);
	        $key  = trim(substr($buf,0,strpos($buf,"\r\n")));
	     
	        $new_key = base64_encode(sha1($key."258EAFA5-E914-47DA-95CA-C5AB0DC85B11",true));
			$new_message = "HTTP/1.1 101 Switching Protocols\r\n";
	        $new_message .= "Upgrade: websocket\r\n";
	        $new_message .= "Sec-WebSocket-Version: 13\r\n";
	        $new_message .= "Connection: Upgrade\r\n";
	        $new_message .= "Sec-WebSocket-Accept: " . $new_key . "\r\n\r\n";
	        socket_write($v,$new_message,strlen($new_message));
	        // socket_write(socket,$upgrade.chr(0), strlen($upgrade.chr(0)));
	        $this->hand[(int)$client]=true;
		}

		//��������
		public  function  decodeData($buff)
		{
			//$buff  ��������֡
			$mask = array();  
	        $data = '';  
	        $msg = unpack('H*',$buff);  //��unpack�����Ӷ����ƽ����ݽ���
	        $head = substr($msg[1],0,2);  
	        if (hexdec($head{1}) === 8) {  
	            $data = false;  
	        }else if (hexdec($head{1}) === 1){  
	            $mask[] = hexdec(substr($msg[1],4,2));  
	            $mask[] = hexdec(substr($msg[1],6,2));  
	            $mask[] = hexdec(substr($msg[1],8,2));  
	            $mask[] = hexdec(substr($msg[1],10,2));  
	           	//����������  �����ӵ�ʱ��ͷ�������  ��ʾ state connecting
	            $s = 12;  
	            $e = strlen($msg[1])-2;  
	            $n = 0;  
	            for ($i=$s; $i<= $e; $i+= 2) {  
	                $data .= chr($mask[$n%4]^hexdec(substr($msg[1],$i,2)));  
	                $n++;  
	            }
	            //�������ݵ��ͻ���
	           	//������ȴ���125 �����ݷֿ�
	           	$block=str_split($data,125);
	           	$mess=array(
	           		'mess'=>$block[0],
	           		);
				return $mess;	           	
	        }
		}

		//��������
		public function send($mess,$v)
		{
			foreach ($this->socs as $keys => $values) {
           		$mess['name']="Tourist's socket:{$v}";
           		$str=json_encode($mess);
           		$writes ="\x81".chr(strlen($str)).$str;
           		// if($this->hand[(int)$values])
           			socket_write($values,$writes,strlen($writes));
           	}
		}
		
	}
