#key pair
resource aws_key_pair my_key{
    key_name = "terra_key_ec2"
    public_key = file("terra_key_ec2.pub")
}
#vpc
resource aws_default_vpc default{

}

#security_group
resource "aws_security_group" "my_security_group" {
    name="auto_sg"
    description = "this will open required port"
    vpc_id = aws_default_vpc.default.id

    #inbound rules
    ingress{
        from_port = 22
        to_port = 22
        protocol = "tcp"
        cidr_blocks =["0.0.0.0/0"]
        description="ssh open"
    }
    ingress{
        from_port = 80
        to_port = 80
        protocol= "tcp"
        cidr_blocks = ["0.0.0.0/0"]
        description = "http open"
    }
    ingress{
        from_port = 8000
        to_port = 8000
        protocol = "tcp"
        cidr_blocks = [ "0.0.0.0/0" ]
        
    }
      ingress{
        from_port = 9090
        to_port = 9090
        protocol = "tcp"
        cidr_blocks = [ "0.0.0.0/0" ]
        
    }
      ingress{
        from_port = 30000
        to_port = 30090
        protocol = "tcp"
        cidr_blocks = [ "0.0.0.0/0" ]
        
    }
    egress {
        from_port = 0
        to_port = 0
        protocol = "-1"  # -1 means all ports
        cidr_blocks = [ "0.0.0.0/0" ]
        description = "all access open "
    }

    # ec2 instance


}
resource "aws_instance" "my_instance" {
    key_name = aws_key_pair.my_key.key_name
    security_groups =[aws_security_group.my_security_group.name]
    instance_type = var.ec2_instance_type
    ami = var.ec2_ami_id
    user_data = file("deploy.sh")
    root_block_device {
      volume_size =var.ec2_root_storage_size
      volume_type = "gp3"
    }
    tags={
        Name="My_Project"
    }
  
}