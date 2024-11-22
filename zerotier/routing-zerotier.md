# Routing between ZeroTier and VPC Networks 

This is going to be a step-by-step guide in how to route your private network to the ZeroTier service. This only requires a little bit of networking know how to accomplish. This will be a walk through from a fresh installation of Ubuntu 24 that is set up in DigitalOcean. Please note that at the time of this writing these instructions might change over time but still may be used as a guide.

## Required Information
| Info | Example
| --- | ---
| ZeroTier Network ID | 48d6023c46c1bfd8
| ZeroTier Interface Name | ztosimha46
| VPC (Private IP) Interface Name | eth1

### Network A
| Machine | Zero Tier IP | VPC (Private) IP | VPC Subnet (CIDR)
| --- | --- | --- | ---
Server/Router | 172.30.52.150 | 10.100.0.2 | 10.100.0.0/24
Client | N/A | 10.100.0.3 | 10.100.0.0/24

### Network B
| Machine | Zero Tier IP | VPC (Private) IP | VPC Subnet (CIDR)
| --- | --- | --- | ---
Server/Router | 172.30.38.118 | 10.8.0.2 | 10.8.0.0/24
Client | N/A | 10.8.0.3 | 10.8.0.0/24

## Installation
>### There must be a minimum of two servers with non-conflicting private subnets in order to be configured properly.
>### For example, if two networks you are attempting to connect together have the same private VPC subnet of `10.100.0.0/24` the configuration will not work. One must be different than the other.

Curl command to install and set up zero ZeroTier.
```bash
sudo apt update
curl -s https://install.zerotier.com | sudo bash
```
```bash
sudo zerotier-cli join 48d6023c46c1bfd8
sudo zerotier-cli listnetworks
```

Output should look like this
```bash
root@zt-server-01:~# sudo zerotier-cli join 48d6023c46c1bfd8
200 join OK
root@zt-server-01:~# sudo zerotier-cli listnetworks
200 listnetworks <nwid> <name> <mac> <status> <type> <dev> <ZT assigned ips>
200 listnetworks 48d6023c46c1bfd8  da:23:6d:02:13:c1 ACCESS_DENIED PRIVATE ztosimha46 -
```
> **YOU MUST GO TO ZEROTIER CENTRAL AND HAVE YOUR DEVICE AUTHORIZED IN ORDER TO CONTINUE**

After your device has been authorized it should look something similar to this
```bash
root@zt-server-01:~# sudo zerotier-cli listnetworks
200 listnetworks <nwid> <name> <mac> <status> <type> <dev> <ZT assigned ips>
200 listnetworks 48d6023c46c1bfd8 it490_fantasy da:23:6d:02:13:c1 OK PRIVATE ztosimha46 172.30.52.150/16
```

## Configuration
We next need to configure the routing table to allow traffic to flow between the VPC and ZeroTier network.

Use the `ip a` command to see the list of all interfaces available to us. Note the interface names as we need to set up the iptables as well.
```bash
root@zt-server-01:~# ip a
1: lo: <LOOPBACK,UP,LOWER_UP> mtu 65536 qdisc noqueue state UNKNOWN group default qlen 1000
    link/loopback 00:00:00:00:00:00 brd 00:00:00:00:00:00
    inet 127.0.0.1/8 scope host lo
       valid_lft forever preferred_lft forever
    inet6 ::1/128 scope host noprefixroute 
       valid_lft forever preferred_lft forever
2: eth0: <BROADCAST,MULTICAST,UP,LOWER_UP> mtu 1500 qdisc fq_codel state UP group default qlen 1000
    link/ether b6:ea:53:63:c6:18 brd ff:ff:ff:ff:ff:ff
    altname enp0s3
    altname ens3
    inet 162.243.218.171/24 brd 162.243.218.255 scope global eth0
       valid_lft forever preferred_lft forever
    inet 10.13.0.5/16 brd 10.13.255.255 scope global eth0
       valid_lft forever preferred_lft forever
    inet6 fe80::b4ea:53ff:fe63:c618/64 scope link proto kernel_ll 
       valid_lft forever preferred_lft forever
3: eth1: <BROADCAST,MULTICAST,UP,LOWER_UP> mtu 1500 qdisc fq_codel state UP group default qlen 1000
    link/ether f2:43:3f:96:b7:dc brd ff:ff:ff:ff:ff:ff
    altname enp0s4
    altname ens4
    inet 10.100.0.2/20 brd 10.100.15.255 scope global eth1
       valid_lft forever preferred_lft forever
    inet6 fe80::f043:3fff:fe96:b7dc/64 scope link proto kernel_ll 
       valid_lft forever preferred_lft forever
4: ztosimha46: <BROADCAST,MULTICAST,UP,LOWER_UP> mtu 2800 qdisc fq_codel state UNKNOWN group default qlen 1000
    link/ether da:23:6d:02:13:c1 brd ff:ff:ff:ff:ff:ff
    inet 172.30.52.150/16 brd 172.30.255.255 scope global ztosimha46
       valid_lft forever preferred_lft forever
    inet6 fe80::d823:6dff:fe02:13c1/64 scope link proto kernel_ll 
       valid_lft forever preferred_lft forever

```
We can see that the interface `ztosimha46` is the one that is connected to our zerotier network. We need to route connections to `eth1` which is our VPC interface.

## Enable IP Forwarding (Server)
To enable ip forwarding we need to execute the following command on each server
```bash
sudo sysctl -w net.ipv4.ip_forward=1
```

## Configure iptables (Server)
This rule enables NAT for outgoing traffic on the specified network interface.If your system acts as a router (e.g., sharing internet access or bridging networks), these rules ensure traffic can flow between the two interfaces.
Run the following commands on each server
```bash
sudo iptables -t nat -A POSTROUTING -o eth1 -j MASQUERADE
sudo iptables -t nat -A POSTROUTING -o ztosimha46 -j MASQUERADE
sudo iptables -A FORWARD -i ztosimha46 -o eth1 -j ACCEPT
sudo iptables -A FORWARD -i eth1 -o ztosimha46 -j ACCEPT
```

## Configure routing table (Server)

The command below routes packets destined for the remote 10.8.0.0/24 network via the zerotier router who's ip is 172.30.38.118, which is accessible through the ztosimha46 interface.

This is important, you must set this routing table for each router that is on the zerotier network or it won't be able to know where to route the connection destined for the remote vpc subnet. So in this case the server we are inputting the following command belongs to a vpc subnet of `10.100.0.0/24` and it wants to connect to the subnet `10.8.0.0/24` via `172.30.38.118` which is the zerotier ip address of the server that it belongs to.

On **Network A Server** run the following
```bash
sudo ip route add 10.8.0.0/24 via 172.30.38.118 dev ztosimha46
```
Change `10.8.0.0/24` to the correct subnet of the vpc network and change `172.30.38.118` as well to the correct zerotier ip address. NOT THE PUBLIC IP ADDRESS.

On **Network B Server** run the following
```bash
sudo ip route add 10.100.0.0/24 via 172.30.52.150 dev ztosimha46
```
Change `10.100.0.0/24` to the correct subnet of the vpc network and change `172.30.52.150` as well to the correct zerotier ip address. NOT THE PUBLIC IP ADDRESS.

## Configure routing table (Client)
Fortunately, we only have to add one command for **each** client on the vpc. Here we are routing connections destined for the `10.8.0.0/24` subnet via `10.100.0.2` which is the ip address of the router that is also the server connected in the zerotier network. Remember to change the subnet `10.8.0.0/24` and gateway ip address `10.100.0.2` to your correct network schema.

On **Network A Client(s)** run the following
```bash
ip route add 10.8.0.0/24 via 10.100.0.2 dev eth1
```
On **Network B Client(s)** run the following
```bash
ip route add 10.100.0.0/24 via 10.8.0.2 dev eth1
```

## Testing

To test your connection we will use the ping command from our client, who's ip belongs to the subnet of `10.100.0./24` to another machine who's ip belongs to the subnet of `10.8.0.0/24`

On a machine on **Network A** run the following command
```bash
ping 10.8.0.3
```
You should get a ICMP reply message. If not, go back and check the steps again to see if you have properly added the iptables and/or ip routing tables.

Same goes for a client on **Network B** run the ping command but for a client on the `10.100.0.0/24`

You can also reach the other zerotier server using it's **private ip address** not it's zerotier ip address. As using it's zerotier ip address will not work unless you set up the managed networks on zerotier central.

For example, to reach the router on **Network A** we just have to ping it's private ip address of `10.8.0.2`

```bash
ping 10.8.0.2
```


## Troubleshooting

If your're still having issure, run the following command

```bash
sudo iptables -t nat -L
```
That should produce an output similar to this. Notice that there are rules under the POSTROUTING policy.

```bash
root@zt-server-01:~# iptables -t nat -L
Chain PREROUTING (policy ACCEPT)
target     prot opt source               destination         

Chain INPUT (policy ACCEPT)
target     prot opt source               destination         

Chain OUTPUT (policy ACCEPT)
target     prot opt source               destination         

Chain POSTROUTING (policy ACCEPT)
target     prot opt source               destination         
MASQUERADE  all  --  anywhere             anywhere            
MASQUERADE  all  --  anywhere             anywhere  
```
You can also run the command to see if your forwarding policies are also correct

```bash
sudo iptables -L
```

That should produce an output similar to this. Notice that there should be two FORWARD policies in there as well.

```bash
root@zt-server-01:~# iptables -L
Chain INPUT (policy ACCEPT)
target     prot opt source               destination         

Chain FORWARD (policy ACCEPT)
target     prot opt source               destination         
ACCEPT     all  --  anywhere             anywhere            
ACCEPT     all  --  anywhere             anywhere            

Chain OUTPUT (policy ACCEPT)
target     prot opt source               destination
```

You can also check your ip route tables on your **server**

```bash
sudo ip route
```

Which also should look similare to this. Here make sure that there is a route going to the **other** subnet `10.8.0.0/24` via the **other** zerotier ip address `172.30.38.118`. It should not be the same private subnet it shares with and the zerotier ip address is not it's own.

```bash
root@zt-server-01:~# ip route
default via 162.243.218.1 dev eth0 proto static 
10.8.0.0/24 via 172.30.38.118 dev ztosimha46 
10.13.0.0/16 dev eth0 proto kernel scope link src 10.13.0.5 
10.100.0.0/20 dev eth1 proto kernel scope link src 10.100.0.2 
162.243.218.0/24 dev eth0 proto kernel scope link src 162.243.218.171 
172.30.0.0/16 dev ztosimha46 proto kernel scope link src 172.30.52.150 
```
You can also check the routing table for your **client** using the same command

```bash
sudo ip route
```
It should look something similar to this. Here we are still using the **other** subnet `10.8.0.0/24` as our destination but **via our router's private ip address** which in this case is `10.100.0.2`
```bash
root@test-server-01:~# ip route 
default via 162.243.248.1 dev eth0 proto static 
10.8.0.0/24 via 10.100.0.2 dev eth1 
10.13.0.0/16 dev eth0 proto kernel scope link src 10.13.0.6 
10.100.0.0/20 dev eth1 proto kernel scope link src 10.100.0.3 
162.243.248.0/24 dev eth0 proto kernel scope link src 162.243.248.188 
```

