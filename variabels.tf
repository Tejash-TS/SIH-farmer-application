variable "ec2_instance_type" {
   default = "c7i-flex.large"
    type= string
  
}
variable "ec2_root_storage_size" {
    default = 30
    type=number
  
}
variable "ec2_ami_id" {
    default ="ami-01a00762f46d584a1"
    type = string
  
}
