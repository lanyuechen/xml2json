<?php

/**
 * xml2json
 * 描述：以XMLReader的方式从url读取一个xml文件，并将其内容转换为json字符串
 * 参数：$url:xml文件地址
 * 返回：json字符串
 * 2014-09-19 by lanyuechen
 * 
 * 转换后，xml标签对应json键名，xml标签内容对应json键值，内容为空，则转换为空字符串。
 * 注意：xml文档结构不应与json结构冲突，否则转换结果可能与期望不符
 * xml文档结构：
 * <a>
 * 	<b>
 * 	 <c>内容1</c>
 * 	 <d>内容2</d>
 * 	 ...
 * 	</b>
 * 	<b>
 * 	 <c>内容3</c>
 * 	 <d>内容4</d>
 * 	 ...
 * 	</b>
 * 	...
 * </a>
 * 转换为json后为：
 * {
 * 	a:[
 * 	 b0:{
 * 	  c:'内容1',
 * 	  b:'内容2',
 * 	  ...
 * 	 },
 * 	 b1:{
 * 	  c:'内容3',
 * 	  b:'内容4',
 * 	  ...
 * 	 },
 * 	 ...
 * 	]
 * }
 */
function xml2json($url){
	 
	$data = array();				//用于保存解析后的数据
	$depth = -1;						//上一次读取到的元素深度
	$stack = array(&$data);	//指针栈，保存每次读取元素的索引
	$index = 0;							//用于给同一标签内的相同标签添加id，防止内容发生覆盖

	$xml = new XMLReader();
	$xml->open($url);
	while($xml->read()){
		if($xml->nodeType == XMLReader::ELEMENT){
			if($xml->depth == $depth){	//当前标签深度等于上一次标签深度，说明此标签与上一标签是兄弟
				$name = $xml->name;				//因此在其父对象上添加新空间，作为当前标签的空间
				if($stack[1][$name]){			//将stack[0]指向当前空间
					$name .= $index++;
				}
				$stack[1][$name] = '';	
				$stack[0] = &$stack[1][$xml->name];	
			}else if($xml->depth > $depth){	//当前标签深度大于上一标签深度，说明此标签是上一标签儿子
				$stack[0][$xml->name] = '';		//给上一标签添加新空间，作为当前标签的空间
				array_unshift($stack, 0);			//压栈，将stack[0]指向当前空间，stack[1]指向上一标签空间
				$stack[0] = &$stack[1][$name];//记得更新深度，++1
				$depth++;
			}else if($xml->depth < $depth){ //当前标签深度小于上一标签深度，说明次标签是上一标签的叔叔
				array_shift($stack);					//因此先出栈，找到上一标签的爷爷，给他爷爷添加子空间，即他叔叔
				$name = $xml->name;						//剩下的工作与深度相同时一样
				if($stack[1][$name]){					//记得再次更新深度，--1
					$name .= $index++;
				}
				$stack[1][$name] = '';
				$stack[0] = &$stack[1][$name];
				$depth--;
			}
		}else if($xml->nodeType == XMLReader::TEXT){
    	$stack[0] = $xml->value;			//如果独到标签内容，把内容值赋给stack[0]——当前指向的存储空间
    }
	}
	$xml->close();
	
	// $data = array_values($data['NewDataSet']);
	
	return json_encode($data);	//返回json数据
}