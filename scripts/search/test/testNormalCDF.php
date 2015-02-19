<?php

function CDF($X){
  if($X>0){
      $x=$X;
  }else{
      $x=-$X;
  }
  if($x>8){
      return 1;
  }
  $sum=$x;
  $value=$x;
  for( $i=1; $i<100; $i++){
      $value=($value*$x*$x/(2*$i+1));
      $sum=$sum+$value;
  }
  $result=0.5+($sum/sqrt(2* pi()))*exp(-($x*$x)/2);
  if($X<0){
      $result=1-$result;
  }
  return $result;
}

for($x=0; $x<100;$x+=1){
    echo "$x=>".(1-CDF($x)).";\n";
}
